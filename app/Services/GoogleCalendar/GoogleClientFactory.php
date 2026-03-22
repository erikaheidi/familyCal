<?php

namespace App\Services\GoogleCalendar;

use App\Models\GoogleAccount;
use Google\Client;

class GoogleClientFactory
{
    public function make(?GoogleAccount $account = null): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google_calendar.client_id'));
        $client->setClientSecret(config('services.google_calendar.client_secret'));
        $client->setRedirectUri(config('services.google_calendar.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes(config('services.google_calendar.scopes', []));

        if ($account) {
            $this->applyAccountToken($client, $account);
        }

        return $client;
    }

    private function applyAccountToken(Client $client, GoogleAccount $account): void
    {
        if ($account->access_token) {
            $token = [
                'access_token' => $account->access_token,
                'created' => now()->timestamp,
            ];

            if ($account->token_expires_at) {
                $token['expires_in'] = max(0, $account->token_expires_at->diffInSeconds(now()));
            }

            $client->setAccessToken($token);
        }

        if ($account->refresh_token && $account->token_expires_at?->isPast()) {
            $refreshedToken = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

            if (! isset($refreshedToken['error'])) {
                $expiresIn = (int) ($refreshedToken['expires_in'] ?? 0);

                $account->forceFill([
                    'access_token' => $refreshedToken['access_token'] ?? $account->access_token,
                    'refresh_token' => $refreshedToken['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
                ])->save();

                $client->setAccessToken([
                    'access_token' => $account->access_token,
                    'created' => now()->timestamp,
                    'expires_in' => $expiresIn,
                ]);
            }
        }
    }
}
