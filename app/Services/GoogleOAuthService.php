<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Company;
use App\Models\GoogleAccount;
use App\Models\Recruiter;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use function Laravel\Ai\agent;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;

class GoogleOAuthService
{
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const GMAIL_PROFILE_ENDPOINT = 'https://gmail.googleapis.com/gmail/v1/users/me/profile';

    private const GMAIL_MESSAGES_ENDPOINT = 'https://gmail.googleapis.com/gmail/v1/users/me/messages';

    private const SYNC_QUERY = 'in:sent OR from:jobs-noreply@linkedin.com OR (from:jobstreet.com AND ("berhasil dikirim" OR "lamaran" OR "aplikasi" OR "status")) OR (subject:(interview OR assessment OR psikotes OR "technical test" OR "coding test" OR offer OR rejection OR "status lamaran" OR "undangan" OR "panggilan") OR "application received" OR "thanks for applying" OR "terima kasih telah melamar")';

    private const MAX_PAGES_PER_SYNC = 5;

    private const MESSAGES_PER_PAGE = 50;

    public function storeAccountFromSocialite(User $user, SocialiteUser $providerUser): GoogleAccount
    {
        return GoogleAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_id' => (string) $providerUser->getId(),
                'gmail_address' => $providerUser->getEmail(),
                'name' => $providerUser->getName() ?: $user->name,
                'access_token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken,
                'token_type' => 'Bearer',
                'scopes' => $providerUser->approvedScopes ?? ['https://www.googleapis.com/auth/gmail.readonly', 'email', 'profile'],
                'expires_at' => $providerUser->expiresIn ? now()->addSeconds((int) $providerUser->expiresIn) : null,
                'connected_at' => now(),
                'last_synced_at' => null,
            ]
        );
    }

    public function syncMailbox(GoogleAccount $googleAccount): array
    {
        $token = $this->refreshAccessTokenIfNeeded($googleAccount);
        $lastSyncedAt = $googleAccount->last_synced_at?->timestamp ?? now()->subDays(90)->timestamp;
        $query = 'after:'.$lastSyncedAt.' ('.self::SYNC_QUERY.')';

        $pageToken = null;
        $synced = 0;

        for ($page = 0; $page < self::MAX_PAGES_PER_SYNC; $page++) {
            $messagesResponse = $this->http()->withToken($token)->get(self::GMAIL_MESSAGES_ENDPOINT, [
                'maxResults' => self::MESSAGES_PER_PAGE,
                'q' => $query,
                'pageToken' => $pageToken,
            ])->throw()->json();

            foreach (Arr::get($messagesResponse, 'messages', []) as $message) {
                $messageId = Arr::get($message, 'id');

                if (blank($messageId)) {
                    continue;
                }

                $detailResponse = $this->http()->withToken($token)->get(self::GMAIL_MESSAGES_ENDPOINT.'/'.$messageId, [
                    'format' => 'full',
                ])->throw()->json();

                $synced += $this->storeEmailMessage($googleAccount, $detailResponse) ? 1 : 0;
            }

            $pageToken = Arr::get($messagesResponse, 'nextPageToken');

            if ($pageToken === null) {
                break;
            }
        }

        $googleAccount->forceFill([
            'last_synced_at' => now(),
        ])->save();

        return [
            'synced' => $synced,
            'last_synced_at' => $googleAccount->last_synced_at,
        ];
    }

    public function disconnect(GoogleAccount $googleAccount): void
    {
        $googleAccount->delete();
    }

    public function refreshAccessTokenIfNeeded(GoogleAccount $googleAccount): string
    {
        if ($googleAccount->expires_at instanceof CarbonInterface && $googleAccount->expires_at->isFuture()) {
            return $googleAccount->access_token;
        }

        if (blank($googleAccount->refresh_token)) {
            throw new RuntimeException('Google account has no refresh token available.');
        }

        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $googleAccount->refresh_token,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $googleAccount->forceFill([
            'access_token' => Arr::get($response, 'access_token'),
            'expires_at' => Arr::get($response, 'expires_in') ? now()->addSeconds((int) Arr::get($response, 'expires_in')) : null,
        ])->save();

        return $googleAccount->access_token;
    }

    private function storeEmailMessage(GoogleAccount $googleAccount, array $messageData): bool
    {
        $headers = collect(Arr::get($messageData, 'payload.headers', []))
            ->mapWithKeys(static fn (array $header): array => [Arr::get($header, 'name') => Arr::get($header, 'value')]);

        $subject = (string) $headers->get('Subject', '');
        $from = (string) $headers->get('From', '');
        $to = (string) $headers->get('To', '');
        $snippet = (string) Arr::get($messageData, 'snippet', '');
        $labelIds = (array) Arr::get($messageData, 'labelIds', []);
        $isSent = in_array('SENT', $labelIds, true);
        $receivedAt = Arr::get($messageData, 'internalDate') ? Carbon::createFromTimestampMs((int) Arr::get($messageData, 'internalDate')) : null;
        $body = $this->extractBodyText(Arr::get($messageData, 'payload', []));
        $attachments = $this->extractAttachments(Arr::get($messageData, 'payload', []));

        $classification = $this->classifyMessage($subject, $snippet, $from, $to, $isSent);

        if ($classification === null) {
            return false;
        }

        $company = Company::firstOrCreate([
            'user_id' => $googleAccount->user_id,
            'name' => $classification['company_name'],
        ]);

        $recruiter = ! empty($classification['recruiter_email']) ? Recruiter::firstOrCreate([
            'user_id' => $googleAccount->user_id,
            'company_id' => $company->id,
            'email' => $classification['recruiter_email'],
        ], [
            'name' => $classification['recruiter_name'] ?? $classification['company_name'],
        ]) : null;

        $gmailMessageId = (string) Arr::get($messageData, 'id');
        $application = Application::where('user_id', $googleAccount->user_id)
            ->where('gmail_message_id', $gmailMessageId)
            ->first();

        $notes = trim($subject."\n\n".$body);
        $summary = $application?->summary;

        // Call AI only if it's a new application or if the previous notes were truncated (shorter)
        if (! $application || strlen($notes) > strlen($application->notes ?? '') + 50) {
            $summary = $this->summarizeMessage($subject, $snippet, $from, $body);
        }

        Application::updateOrCreate(
            [
                'user_id' => $googleAccount->user_id,
                'gmail_message_id' => $gmailMessageId,
            ],
            [
                'company_id' => $company->id,
                'recruiter_id' => $recruiter?->id,
                'position_title' => $classification['position_title'],
                'status' => $classification['status'],
                'application_date' => $receivedAt?->toDateString() ?? now()->toDateString(),
                'source' => $isSent ? 'Sent Mail' : ($classification['source'] ?? 'Gmail'),
                'notes' => $notes,
                'summary' => $summary,
                'attachments' => $attachments,
            ]
        );

        return true;
    }

    private function classifyMessage(string $subject, string $snippet, string $from = '', string $to = '', bool $isSent = false): ?array
    {
        $fromLower = Str::lower($from);

        // 1. Exclude social network invites, newsletters, digests, promos, and tips
        if (Str::contains($fromLower, [
            'invitations@linkedin.com',
            'newsletters-noreply@linkedin.com',
            'jobalerts-noreply@linkedin.com',
            'messaging-digest-noreply@linkedin.com',
            'messages-noreply@linkedin.com',
            'notifications-noreply@linkedin.com',
            'em.linkedin.com',
        ])) {
            return null;
        }

        $haystack = Str::lower($subject.' '.$snippet);

        if (Str::contains($haystack, ['diskon', 'kesalahan di cv', 'insights on ai', 'tayangan minggu lalu', 'menyetujui undangan', 'rekomendasi lowongan', 'telah ditutup', 'mengirimi anda pesan'])) {
            return null;
        }

        $platformSource = match (true) {
            Str::contains($fromLower, 'linkedin.com') => 'LinkedIn',
            Str::contains($fromLower, ['jobstreet.com', 'jobstreet.co.id']) => 'JobStreet',
            Str::contains($fromLower, 'glints.com') => 'Glints',
            Str::contains($fromLower, 'kalibrr.com') => 'Kalibrr',
            Str::contains($fromLower, 'indeed.com') => 'Indeed',
            Str::contains($fromLower, 'techinasia.com') => 'Tech in Asia',
            default => null,
        };

        // 2. Identify Status
        $status = match (true) {
            Str::contains($haystack, ['interview', 'panggilan interview', 'wawancara', 'invitation to interview']) => 'interview',
            Str::contains($haystack, ['assessment', 'psikotes', 'technical test', 'coding test', 'take-home test', 'skill test', 'quiz', 'hackerrank', 'gorilla']) => 'assessment',
            Str::contains($haystack, ['offer', 'congratulations', 'offering letter', 'tawaran kerja', 'surat penawaran']) => 'offer',
            Str::contains($haystack, ['reject', 'rejection', 'unfortunately', 'not moving forward', 'tidak dapat melanjutkan', 'belum sesuai', 'dieliminasi', 'mohon maaf']) => 'rejection',
            Str::contains($haystack, ['application', 'applied', 'thanks for applying', 'received your application', 'lamaran', 'melamar', 'terima kasih telah', 'your application was sent', 'lamaran anda', 'lamaranmu berhasil dikirim', 'submitted successfully', 'successfully submitted']) => 'applied',
            $isSent => 'applied',
            default => null,
        };

        if ($status === null) {
            return null;
        }

        // 3. Extract Company & Position
        $companyName = $this->extractCompanyName($subject, $snippet, $from, $to, $isSent, $platformSource);
        $positionTitle = $this->extractPositionTitle($subject, $snippet);

        if (in_array(strtolower($positionTitle), ['job application', 'ada undangan untuk anda', 'ririn baru mengirimi anda pesan', 'yudi baru mengirimi anda pesan'])) {
            return null;
        }

        $recruiterSource = $isSent ? $to : $from;

        return [
            'status' => $status,
            'position_title' => $positionTitle,
            'company_name' => $companyName,
            'source' => $isSent ? 'Sent Mail' : ($platformSource ?? 'Gmail'),
            'recruiter_name' => $this->extractRecruiterName($recruiterSource),
            'recruiter_email' => $this->extractRecruiterEmail($recruiterSource),
        ];
    }

    private function extractPositionTitle(string $subject, string $snippet): string
    {
        $fullText = $subject.' '.$snippet;

        if (preg_match('/posisi\s+(.+?)\s+berhasil\s+dikirimkan/i', $fullText, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/^([A-Za-z0-9\/\s\-\+\#\.]+?)\s*[\-\_\|]\s*(?:Alif|Application|CV|Resume)/i', $subject, $matches) === 1) {
            $pos = trim($matches[1]);
            if (strlen($pos) >= 3 && strlen($pos) <= 60) {
                return $pos;
            }
        }

        if (preg_match('/Application sent to\s+(.+?)\s+at\s+/i', $fullText, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/(?:application for|aplikasi (?:anda )?untuk|lamaran untuk)\s+(.+?)\s+(?:at|di|berhasil|\-|$)/i', $fullText, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/(?:position|role|as|sebagai)\s+([A-Za-z0-9\/\s\-\+]+?)(?:\s+at|\s+di|\.|\,|\-|$)/i', $fullText, $matches) === 1) {
            $pos = trim($matches[1]);
            if (strlen($pos) >= 3 && strlen($pos) <= 60) {
                return $pos;
            }
        }

        $cleanSubject = preg_replace('/^(Re:|Fwd:|FW:)\s*/i', '', $subject);
        if (strlen($cleanSubject) >= 3 && strlen($cleanSubject) <= 70) {
            return trim($cleanSubject);
        }

        return 'Job Application';
    }

    private function extractCompanyName(string $subject, string $snippet, string $from = '', string $to = '', bool $isSent = false, ?string $platformSource = null): string
    {
        $fullText = $subject.' '.$snippet;

        if (preg_match('/berhasil dikirimkan ke\s+(.+?)(?:\.|\,|\n|$)/i', $fullText, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/lamaran Anda sudah dikirim ke\s+(.+?)(?:\.|\,|\n|$)/i', $fullText, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/Tim Rekrutmen\s+(.+?)(?:\,|\(|\.|\n|$)/i', $snippet, $matches) === 1) {
            $comp = trim($matches[1]);
            if (strlen($comp) >= 2 && strlen($comp) <= 60) {
                return $comp;
            }
        }

        if (preg_match('/(?:at|di)\s+([A-Z0-9][A-Za-z0-9\&\-\.\s]+?)(?:\s*\-|\s*[\,]|[\.]|\s+for|\s+untuk|\s+position|\s+role|$)/i', $fullText, $matches) === 1) {
            $company = trim($matches[1]);
            if (strlen($company) >= 2 && strlen($company) <= 50 && ! in_array(strtolower($company), ['linkedin', 'jobstreet', 'glints', 'kalibrr', 'indeed', 'gmail'])) {
                return $company;
            }
        }

        if (preg_match('/(PT\s+[A-Za-z0-9\s\&\-]+|[A-Z][A-Za-z0-9\s\&\-]+\s+(?:Group|Technology|Teknologi|Indonesia|Inc|Ltd|Corp))/i', $fullText, $matches) === 1) {
            return trim($matches[1]);
        }

        $emailTarget = $isSent ? $to : $from;
        $email = $this->extractRecruiterEmail($emailTarget);
        if ($email !== null && str_contains($email, '@')) {
            $domain = explode('@', $email)[1] ?? '';
            $domainName = explode('.', $domain)[0] ?? '';
            if (! empty($domainName) && ! in_array(strtolower($domainName), ['gmail', 'yahoo', 'hotmail', 'outlook', 'linkedin', 'jobstreet', 'glints', 'kalibrr', 'indeed'])) {
                return ucfirst($domainName);
            }
        }

        return $platformSource ?? 'Company';
    }

    private function extractRecruiterEmail(string $from): ?string
    {
        return preg_match('/<([^<>@\s]+@[^<>@\s]+)>/', $from, $matches) === 1 ? $matches[1] : (filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : null);
    }

    private function extractRecruiterName(string $from): ?string
    {
        return preg_match('/^([^<]+)</', $from, $matches) === 1 ? trim($matches[1], ' "') : null;
    }

    private function extractBodyText(array $payload): string
    {
        if (! empty($payload['body']['data'])) {
            return $this->decodeBase64Url($payload['body']['data']);
        }

        if (! empty($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/plain') {
                    return $this->decodeBase64Url($part['body']['data'] ?? '');
                }
            }
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/html') {
                    return strip_tags($this->decodeBase64Url($part['body']['data'] ?? ''));
                }
            }
            foreach ($payload['parts'] as $part) {
                if (isset($part['parts'])) {
                    $text = $this->extractBodyText($part);
                    if (! empty($text)) {
                        return $text;
                    }
                }
            }
        }

        return '';
    }

    private function extractAttachments(array $payload): array
    {
        $attachments = [];

        if (! empty($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if (! empty($part['filename']) && ! empty($part['body']['attachmentId'])) {
                    $attachments[] = [
                        'id' => $part['body']['attachmentId'],
                        'filename' => $part['filename'],
                        'mimeType' => $part['mimeType'] ?? 'application/octet-stream',
                        'size' => $part['body']['size'] ?? 0,
                    ];
                }

                if (isset($part['parts'])) {
                    $attachments = array_merge($attachments, $this->extractAttachments($part));
                }
            }
        }

        return $attachments;
    }

    public function getAttachmentData(GoogleAccount $googleAccount, string $messageId, string $attachmentId): ?array
    {
        $token = $this->refreshAccessTokenIfNeeded($googleAccount);

        $response = $this->http()->withToken($token)
            ->get(self::GMAIL_MESSAGES_ENDPOINT.'/'.$messageId.'/attachments/'.$attachmentId)
            ->json();

        if (empty($response['data'])) {
            return null;
        }

        return [
            'data' => $this->decodeBase64Url($response['data']),
            'size' => $response['size'] ?? 0,
        ];
    }

    private function decodeBase64Url(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }

    private function summarizeMessage(string $subject, string $snippet, string $from, string $body = ''): string
    {
        $content = trim($subject.' '.$snippet.' '.$from."\n\n".$body);
        $content = Str::limit($content, 3500);

        try {
            $response = agent(instructions: 'You are an assistant. Summarize this job application email. Output only the summary in 1-2 short sentences, no greetings.')
                ->prompt($content);
                
            return trim($response->text);
        } catch (\Throwable $e) {
            report($e);
            return Str::limit($content, 220);
        }
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson();
    }
}
