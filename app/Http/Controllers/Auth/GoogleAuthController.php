<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGmailJob;
use App\Models\User;
use App\Services\GoogleOAuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const GMAIL_SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'email',
        'profile',
    ];

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(self::GMAIL_SCOPES)
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent select_account',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request, GoogleOAuthService $googleOAuthService): RedirectResponse
    {
        if ($request->query('error') === 'access_denied') {
            return to_route('login')->with('status', 'google-auth-denied');
        }

        try {
            $providerUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            logger()->error('Google auth callback failed', ['error' => $e->getMessage()]);

            return to_route('login')->with('status', 'google-auth-failed');
        }

        $user = User::firstOrCreate(
            ['email' => $providerUser->getEmail()],
            [
                'name' => $providerUser->getName() ?? $providerUser->getEmail(),
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ]
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $googleAccount = $googleOAuthService->storeAccountFromSocialite($user, $providerUser);

        Auth::login($user, true);
        $request->session()->regenerate();

        SyncGmailJob::dispatch($googleAccount);

        return redirect()->intended(route('dashboard'))->with('status', 'google-auth-success');
    }
}
