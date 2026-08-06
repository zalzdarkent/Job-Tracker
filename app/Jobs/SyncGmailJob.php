<?php

namespace App\Jobs;

use App\Models\GoogleAccount;
use App\Services\GoogleOAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncGmailJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(public GoogleAccount $googleAccount) {}

    public function handle(GoogleOAuthService $googleOAuthService): void
    {
        try {
            $googleOAuthService->syncMailbox($this->googleAccount);
        } catch (\Throwable $e) {
            Log::error('Gmail sync failed for account #'.$this->googleAccount->id, [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
