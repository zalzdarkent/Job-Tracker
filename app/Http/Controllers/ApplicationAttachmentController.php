<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\GoogleAccount;
use App\Services\GoogleOAuthService;
use Illuminate\Http\Response;

class ApplicationAttachmentController extends Controller
{
    public function show(Application $application, string $attachmentId, GoogleOAuthService $googleOAuthService)
    {
        $googleAccount = GoogleAccount::where('user_id', $application->user_id)->first();
        if (! $googleAccount) {
            abort(404, 'Google account not found.');
        }

        $attachments = $application->attachments ?? [];
        $attachment = collect($attachments)->firstWhere('id', $attachmentId);

        if (! $attachment) {
            abort(404, 'Attachment not found.');
        }

        $fileData = $googleOAuthService->getAttachmentData($googleAccount, $application->gmail_message_id, $attachmentId);

        if (! $fileData) {
            abort(404, 'Failed to fetch attachment from Gmail.');
        }

        $mimeType = $attachment['mimeType'] ?? 'application/octet-stream';
        $filename = $attachment['filename'] ?? 'attachment';

        return response($fileData['data'], 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
