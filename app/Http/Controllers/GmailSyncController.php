<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Services\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GmailSyncController extends Controller
{
    public function __construct(private readonly GoogleOAuthService $googleOAuthService) {}

    public function sync(Request $request): StreamedResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $googleAccount = GoogleAccount::query()
            ->where('user_id', $user->id)
            ->whereNotNull('refresh_token')
            ->first();

        if ($googleAccount === null) {
            return response()->json([
                'message' => 'No connected Gmail account found. Please connect your Gmail first.',
                'synced' => 0,
            ], 422);
        }

        return response()->stream(function () use ($googleAccount, $request) {
            $onProgress = function ($data) {
                echo "event: progress\n";
                echo "data: " . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $forceFullSync = $request->boolean('full');
                $result = $this->googleOAuthService->syncMailbox($googleAccount, $onProgress, $forceFullSync);

                $message = $result['synced'] > 0
                    ? "Sync complete! Found {$result['synced']} new application(s)."
                    : 'Sync complete. No new applications found.';

                echo "event: complete\n";
                echo "data: " . json_encode([
                    'message' => $message,
                    'synced' => $result['synced'],
                    'last_synced_at' => $result['last_synced_at'],
                ]) . "\n\n";
            } catch (\Throwable $e) {
                Log::error('Gmail sync failed via UI for user #'.$googleAccount->user_id, [
                    'error' => $e->getMessage(),
                ]);

                echo "event: error\n";
                echo "data: " . json_encode([
                    'message' => 'Sync failed: '.$e->getMessage(),
                    'synced' => 0,
                ]) . "\n\n";
            }

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
