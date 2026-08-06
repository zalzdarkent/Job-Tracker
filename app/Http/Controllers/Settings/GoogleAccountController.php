<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGmailJob;
use App\Models\GoogleAccount;
use App\Services\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAccountController extends Controller
{
    public function redirectToGoogle(Request $request, GoogleOAuthService $googleOAuthService): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/gmail.readonly',
                'email',
                'profile',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent select_account',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request, GoogleOAuthService $googleOAuthService): RedirectResponse
    {
        $providerUser = Socialite::driver('google')->user();

        $googleAccount = $googleOAuthService->storeAccountFromSocialite($request->user(), $providerUser);

        SyncGmailJob::dispatch($googleAccount);

        return to_route('profile.edit');
    }

    public function sync(Request $request): RedirectResponse
    {
        $googleAccount = $request->user()->googleAccount;

        if (! $googleAccount instanceof GoogleAccount) {
            return to_route('profile.edit')->with('status', 'google-account-missing');
        }

        SyncGmailJob::dispatch($googleAccount);

        return to_route('profile.edit')->with('status', 'google-account-synced');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->googleAccount()?->delete();

        return to_route('profile.edit')->with('status', 'google-account-disconnected');
    }
}
