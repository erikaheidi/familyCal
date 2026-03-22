<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Services\GoogleCalendar\GoogleCalendarService;
use App\Services\GoogleCalendar\GoogleClientFactory;
use Google\Service\Oauth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleCalendarAuthController extends Controller
{
    public function connect(GoogleClientFactory $clientFactory): RedirectResponse
    {
        $client = $clientFactory->make();

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(
        Request $request,
        GoogleClientFactory $clientFactory,
        GoogleCalendarService $calendarService
    ): RedirectResponse {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('google-calendar.edit')
                ->with('error', 'Google authorization was cancelled.');
        }

        $code = $request->string('code');

        if ($code->isEmpty()) {
            return redirect()
                ->route('google-calendar.edit')
                ->with('error', 'Missing authorization code from Google.');
        }

        $client = $clientFactory->make();
        $token = $client->fetchAccessTokenWithAuthCode($code->toString());

        if (isset($token['error'])) {
            return redirect()
                ->route('google-calendar.edit')
                ->with('error', 'Unable to authenticate with Google.');
        }

        $client->setAccessToken($token);

        $oauthService = new Oauth2($client);
        $profile = $oauthService->userinfo->get();

        $account = GoogleAccount::query()->firstOrNew(['user_id' => $user->id]);
        $expiresIn = (int) ($token['expires_in'] ?? 0);

        $account->forceFill([
            'google_account_id' => (string) $profile->getId(),
            'email' => (string) $profile->getEmail(),
            'name' => $profile->getName() ?: (string) $profile->getEmail(),
            'avatar_url' => $profile->getPicture(),
            'access_token' => $token['access_token'] ?? $account->access_token,
            'refresh_token' => $token['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
            'scopes' => isset($token['scope'])
                ? array_filter(explode(' ', (string) $token['scope']))
                : config('services.google_calendar.scopes', []),
        ])->save();

        $calendarService->syncCalendars($account);

        return redirect()
            ->route('google-calendar.edit')
            ->with('status', 'google-calendar-connected');
    }
}
