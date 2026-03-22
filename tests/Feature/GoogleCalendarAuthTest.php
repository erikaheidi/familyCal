<?php

use App\Models\User;

test('connect redirects to google auth', function () {
    config()->set('services.google_calendar.client_id', 'test-client');
    config()->set('services.google_calendar.client_secret', 'test-secret');
    config()->set('services.google_calendar.redirect', 'https://example.test/settings/google-calendar/callback');
    config()->set('services.google_calendar.scopes', ['https://www.googleapis.com/auth/calendar']);

    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('google-calendar.connect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('callback without code redirects with error', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('google-calendar.callback'));

    $response->assertRedirect(route('google-calendar.edit'))
        ->assertSessionHas('error');
});

test('callback with error redirects with error', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('google-calendar.callback', ['error' => 'access_denied']));

    $response->assertRedirect(route('google-calendar.edit'))
        ->assertSessionHas('error');
});
