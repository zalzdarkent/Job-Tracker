<?php

use App\Jobs\SyncGmailJob;
use App\Models\GoogleAccount;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects the user to the google consent screen', function (): void {
    Socialite::fake('google');

    $response = $this->get('/auth/google');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->not()->toBeEmpty();
});

it('creates a user and logs them in after the google callback', function (): void {
    Queue::fake();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Alif Fadillah',
        'email' => 'alif@example.com',
        'token' => 'access-token',
        'refreshToken' => 'refresh-token',
        'expiresIn' => 3600,
        'approvedScopes' => ['https://www.googleapis.com/auth/gmail.readonly', 'email', 'profile'],
    ]));

    $response = $this->get('/auth/google/callback?code=auth-code');

    $response->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'alif@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();

    $account = GoogleAccount::query()->first();

    expect($account)->not->toBeNull();
    expect($account->user_id)->toBe($user->id);
    expect($account->google_id)->toBe('google-123');
    expect($account->gmail_address)->toBe('alif@example.com');

    Queue::assertPushed(SyncGmailJob::class);
});

it('logs in an existing user after the google callback', function (): void {
    Queue::fake();

    $user = User::factory()->create(['email' => 'alif@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Alif Fadillah',
        'email' => 'alif@example.com',
        'token' => 'access-token',
        'refreshToken' => 'refresh-token',
        'expiresIn' => 3600,
    ]));

    $this->get('/auth/google/callback?code=auth-code');

    $this->assertAuthenticatedAs($user);

    expect(User::query()->count())->toBe(1);
});

it('redirects back to the login screen when the user denies access', function (): void {
    $response = $this->get('/auth/google/callback?error=access_denied');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
