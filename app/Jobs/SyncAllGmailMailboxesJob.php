<?php

namespace App\Jobs;

use App\Models\GoogleAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncAllGmailMailboxesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        GoogleAccount::query()
            ->whereNotNull('refresh_token')
            ->each(static function (GoogleAccount $googleAccount): void {
                SyncGmailJob::dispatch($googleAccount);
            });
    }
}
