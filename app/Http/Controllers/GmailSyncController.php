<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Services\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GmailSyncController extends Controller
{
    public function __construct(private readonly GoogleOAuthService $googleOAuthService) {}

    public function sync(Request $request): JsonResponse
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

        try {
            $result = $this->googleOAuthService->syncMailbox($googleAccount);

            return response()->json([
                'message' => $result['synced'] > 0
                    ? "Sync complete! Found {$result['synced']} new application(s)."
                    : 'Sync complete. No new applications found.',
                'synced' => $result['synced'],
                'last_synced_at' => $googleAccount->fresh()?->last_synced_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gmail sync failed via UI for user #'.$user->id, [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Sync failed: '.$e->getMessage(),
                'synced' => 0,
            ], 500);
        }
    }
}
