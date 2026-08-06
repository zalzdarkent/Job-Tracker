<?php

use App\Models\Application;
use App\Models\GoogleAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects the user to the google consent screen', function (): void {
    $user = User::factory()->create();

    Socialite::fake('google');

    $response = $this->actingAs($user)->get('/settings/google/connect');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->not()->toBeEmpty();
});

it('stores the google account after oauth callback', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Alif Fadillah',
        'email' => 'alif@example.com',
        'token' => 'access-token',
        'refreshToken' => 'refresh-token',
        'expiresIn' => 3600,
        'approvedScopes' => ['https://www.googleapis.com/auth/gmail.readonly', 'email', 'profile'],
    ]));

    $response = $this->actingAs($user)
        ->get('/settings/google/callback?code=auth-code');

    $response->assertRedirect('/settings/profile');

    $account = GoogleAccount::query()->first();

    expect($account)->not->toBeNull();
    expect($account->user_id)->toBe($user->id);
    expect($account->google_id)->toBe('google-123');
    expect($account->gmail_address)->toBe('alif@example.com');
    expect($account->scopes)->toBe([
        'https://www.googleapis.com/auth/gmail.readonly',
        'email',
        'profile',
    ]);
});

it('syncs gmail messages into applications', function (): void {
    $user = User::factory()->create();

    GoogleAccount::create([
        'user_id' => $user->id,
        'google_id' => 'google-123',
        'gmail_address' => 'alif@example.com',
        'name' => $user->name,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'token_type' => 'Bearer',
        'scopes' => ['https://www.googleapis.com/auth/gmail.readonly'],
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
    ]);

    Http::fake([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg-1*' => Http::response([
            'id' => 'msg-1',
            'threadId' => 'thread-1',
            'snippet' => 'Your application at Acme was received and we would like to schedule an interview.',
            'internalDate' => (string) (now()->subDay()->timestamp * 1000),
            'payload' => [
                'headers' => [
                    ['name' => 'Subject', 'value' => 'Application for Backend Engineer'],
                    ['name' => 'From', 'value' => 'Acme Recruiter <jobs@acme.test>'],
                    ['name' => 'Date', 'value' => now()->subDay()->toRfc2822String()],
                ],
            ],
        ]),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages*' => Http::response([
            'messages' => [
                ['id' => 'msg-1'],
            ],
        ]),
    ]);

    $response = $this->actingAs($user)->post('/settings/google/sync');

    $response->assertRedirect('/settings/profile');

    $this->assertDatabaseHas('applications', [
        'user_id' => $user->id,
        'position_title' => 'Backend Engineer',
        'status' => 'interview',
        'source' => 'Gmail',
    ]);

    expect(Application::query()->count())->toBe(1);
});

it('refreshes an expired access token before syncing', function (): void {
    $user = User::factory()->create();

    GoogleAccount::create([
        'user_id' => $user->id,
        'google_id' => 'google-123',
        'gmail_address' => 'alif@example.com',
        'name' => $user->name,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'token_type' => 'Bearer',
        'scopes' => ['https://www.googleapis.com/auth/gmail.readonly'],
        'expires_at' => now()->subMinute(),
        'connected_at' => now(),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'fresh-token',
            'expires_in' => 3599,
        ]),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages*' => Http::response([
            'messages' => [],
        ]),
    ]);

    $this->actingAs($user)->post('/settings/google/sync');

    $account = GoogleAccount::query()->first();

    expect($account->access_token)->toBe('fresh-token');
    expect($account->expires_at->isFuture())->toBeTrue();
});

it('does not import the same gmail message twice', function (): void {
    $user = User::factory()->create();

    GoogleAccount::create([
        'user_id' => $user->id,
        'google_id' => 'google-123',
        'gmail_address' => 'alif@example.com',
        'name' => $user->name,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'token_type' => 'Bearer',
        'scopes' => ['https://www.googleapis.com/auth/gmail.readonly'],
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
    ]);

    Http::fake([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg-1*' => Http::response([
            'id' => 'msg-1',
            'threadId' => 'thread-1',
            'snippet' => 'Thanks for applying to Acme, we would like to schedule an interview.',
            'internalDate' => (string) (now()->subDay()->timestamp * 1000),
            'payload' => [
                'headers' => [
                    ['name' => 'Subject', 'value' => 'Application for Backend Engineer'],
                    ['name' => 'From', 'value' => 'Acme Recruiter <jobs@acme.test>'],
                    ['name' => 'Date', 'value' => now()->subDay()->toRfc2822String()],
                ],
            ],
        ]),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages*' => Http::response([
            'messages' => [
                ['id' => 'msg-1'],
            ],
        ]),
    ]);

    $this->actingAs($user)->post('/settings/google/sync');
    $this->actingAs($user)->post('/settings/google/sync');

    expect(Application::query()->count())->toBe(1);
});
