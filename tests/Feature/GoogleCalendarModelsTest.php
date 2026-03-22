<?php

use App\Models\CalendarEvent;
use App\Models\CalendarSyncState;
use App\Models\GoogleAccount;
use App\Models\GoogleCalendar;
use App\Models\User;

test('google account belongs to a user', function () {
    $user = User::factory()->create();
    $account = GoogleAccount::factory()->create(['user_id' => $user->id]);

    expect($account->user->is($user))->toBeTrue();
});

test('google account has calendars', function () {
    $account = GoogleAccount::factory()
        ->has(GoogleCalendar::factory()->count(2), 'calendars')
        ->create();

    expect($account->calendars)->toHaveCount(2);
});

test('google calendar has events and sync state', function () {
    $calendar = GoogleCalendar::factory()
        ->has(CalendarEvent::factory()->count(3), 'events')
        ->has(CalendarSyncState::factory(), 'syncState')
        ->create();

    expect($calendar->events)->toHaveCount(3);
    expect($calendar->syncState)->not->toBeNull();
});

test('calendar event belongs to a calendar', function () {
    $calendar = GoogleCalendar::factory()->create();
    $event = CalendarEvent::factory()->create(['google_calendar_id' => $calendar->id]);

    expect($event->googleCalendar->is($calendar))->toBeTrue();
});

test('calendar sync state belongs to a calendar', function () {
    $calendar = GoogleCalendar::factory()->create();
    $state = CalendarSyncState::factory()->create(['google_calendar_id' => $calendar->id]);

    expect($state->googleCalendar->is($calendar))->toBeTrue();
});
