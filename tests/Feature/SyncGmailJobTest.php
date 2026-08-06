<?php

use App\Jobs\SyncAllGmailMailboxesJob;
use App\Jobs\SyncGmailJob;
use App\Models\GoogleAccount;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

it('dispatches a sync job for every connected google account', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $secondUser = User::factory()->create();

    GoogleAccount::create([
        'user_id' => $user->id,
        'google_id' => 'google-1',
        'gmail_address' => 'one@example.com',
        'name' => $user->name,
        'access_token' => 'token',
        'refresh_token' => 'refresh-token',
        'token_type' => 'Bearer',
        'scopes' => ['https://www.googleapis.com/auth/gmail.readonly'],
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
    ]);

    GoogleAccount::create([
        'user_id' => $secondUser->id,
        'google_id' => 'google-2',
        'gmail_address' => 'two@example.com',
        'name' => $secondUser->name,
        'access_token' => 'token',
        'refresh_token' => 'refresh-token',
        'token_type' => 'Bearer',
        'scopes' => ['https://www.googleapis.com/auth/gmail.readonly'],
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
    ]);

    (new SyncAllGmailMailboxesJob)->handle();

    Queue::assertPushed(SyncGmailJob::class, 2);
});

it('schedules automatic gmail synchronization', function (): void {
    $schedule = app(Schedule::class);

    $event = collect($schedule->events())
        ->first(static fn (object $event): bool => str_contains($event->getSummaryForDisplay(), 'SyncAllGmailMailboxes'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 */4 * * *');
});
