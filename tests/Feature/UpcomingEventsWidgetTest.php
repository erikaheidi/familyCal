<?php

use App\Models\CalendarEvent;
use App\Models\GoogleAccount;
use App\Models\GoogleCalendar;
use App\Models\User;
use Livewire\Livewire;

test('upcoming events widget shows events per calendar', function () {
    $user = User::factory()->create();
    $account = GoogleAccount::factory()->create(['user_id' => $user->id]);

    $familyCalendar = GoogleCalendar::factory()->create([
        'google_account_id' => $account->id,
        'name' => 'Family Calendar',
        'is_selected' => true,
    ]);

    $schoolCalendar = GoogleCalendar::factory()->create([
        'google_account_id' => $account->id,
        'name' => 'School Calendar',
        'is_selected' => true,
    ]);

    CalendarEvent::factory()->create([
        'google_calendar_id' => $familyCalendar->id,
        'summary' => 'Family Dinner',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
    ]);

    CalendarEvent::factory()->create([
        'google_calendar_id' => $schoolCalendar->id,
        'summary' => 'School Assembly',
        'start_at' => now()->addDays(2),
        'end_at' => now()->addDays(2)->addHour(),
    ]);

    Livewire::actingAs($user)
        ->test('dashboard.upcoming-events')
        ->assertSee('Family Calendar')
        ->assertSee('Family Dinner')
        ->assertSee('School Calendar')
        ->assertSee('School Assembly');
});

test('upcoming events widget limits events per calendar', function () {
    $user = User::factory()->create();
    $account = GoogleAccount::factory()->create(['user_id' => $user->id]);
    $calendar = GoogleCalendar::factory()->create([
        'google_account_id' => $account->id,
        'name' => 'Family Calendar',
        'is_selected' => true,
    ]);

    foreach (range(1, 5) as $count) {
        CalendarEvent::factory()->create([
            'google_calendar_id' => $calendar->id,
            'summary' => "Event {$count}",
            'start_at' => now()->addDays($count),
            'end_at' => now()->addDays($count)->addHour(),
        ]);
    }

    Livewire::actingAs($user)
        ->test('dashboard.upcoming-events')
        ->assertSee('Event 1')
        ->assertSee('Event 2')
        ->assertSee('Event 3')
        ->assertSee('Event 4')
        ->assertDontSee('Event 5');
});

test('upcoming events widget shows empty state per calendar', function () {
    $user = User::factory()->create();
    $account = GoogleAccount::factory()->create(['user_id' => $user->id]);

    GoogleCalendar::factory()->create([
        'google_account_id' => $account->id,
        'name' => 'Family Calendar',
        'is_selected' => true,
    ]);

    Livewire::actingAs($user)
        ->test('dashboard.upcoming-events')
        ->assertSee('No upcoming events found for this calendar.');
});

test('upcoming events widget shows all selected calendars', function () {
    $user = User::factory()->create();
    $account = GoogleAccount::factory()->create(['user_id' => $user->id]);

    $calendars = collect([
        'Calendar A',
        'Calendar B',
        'Calendar C',
        'Calendar D',
    ])->map(fn (string $name) => GoogleCalendar::factory()->create([
        'google_account_id' => $account->id,
        'name' => $name,
        'is_selected' => true,
    ]));

    Livewire::actingAs($user)
        ->test('dashboard.upcoming-events')
        ->assertSee('Calendar A')
        ->assertSee('Calendar B')
        ->assertSee('Calendar C')
        ->assertSee('Calendar D');
});
