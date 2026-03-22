<?php

use App\Models\CalendarEvent;
use App\Models\GoogleCalendar;
use App\Services\GoogleCalendar\GoogleCalendarService;
use App\Services\GoogleCalendar\GoogleClientFactory;
use Google\Service\Calendar\Event;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;

test('upsert event stores timed event details', function () {
    $calendar = GoogleCalendar::factory()->create();
    $service = new GoogleCalendarService(mock(GoogleClientFactory::class));

    $event = new Event([
        'id' => 'evt-123',
        'status' => 'confirmed',
        'summary' => 'Family Dinner',
        'description' => 'Pizza night',
        'location' => 'Home',
        'htmlLink' => 'https://calendar.google.com/event?eid=123',
        'updated' => '2026-03-21T10:00:00Z',
        'start' => ['dateTime' => '2026-03-22T18:00:00Z'],
        'end' => ['dateTime' => '2026-03-22T19:00:00Z'],
    ]);

    $service->upsertEvent($calendar, $event);

    $stored = CalendarEvent::query()->firstOrFail();

    expect($stored->google_event_id)->toBe('evt-123');
    expect($stored->summary)->toBe('Family Dinner');
    expect($stored->is_all_day)->toBeFalse();
    expect($stored->start_at?->toRfc3339String())
        ->toBe(Carbon::parse('2026-03-22T18:00:00Z')->toRfc3339String());
});

test('upsert event stores all day event details', function () {
    $calendar = GoogleCalendar::factory()->create();
    $service = new GoogleCalendarService(mock(GoogleClientFactory::class));

    $event = new Event([
        'id' => 'evt-456',
        'status' => 'confirmed',
        'summary' => 'Birthday',
        'start' => ['date' => '2026-03-23'],
        'end' => ['date' => '2026-03-24'],
    ]);

    $service->upsertEvent($calendar, $event);

    $stored = CalendarEvent::query()->firstOrFail();

    expect($stored->is_all_day)->toBeTrue();
    expect($stored->start_at?->toDateString())->toBe('2026-03-23');
});

test('cancelled event removes existing record', function () {
    $calendar = GoogleCalendar::factory()->create();
    $service = new GoogleCalendarService(mock(GoogleClientFactory::class));

    CalendarEvent::factory()->create([
        'google_calendar_id' => $calendar->id,
        'google_event_id' => 'evt-789',
    ]);

    $event = new Event([
        'id' => 'evt-789',
        'status' => 'cancelled',
    ]);

    $service->upsertEvent($calendar, $event);

    expect(CalendarEvent::query()->count())->toBe(0);
});
