<?php

use App\Jobs\SyncGoogleCalendars;
use App\Models\GoogleAccount;
use App\Services\GoogleCalendar\GoogleCalendarService;
use function Pest\Laravel\mock;

test('sync job calls calendar and event sync for each account', function () {
    GoogleAccount::factory()->count(2)->create();

    $service = mock(GoogleCalendarService::class);
    $service->shouldReceive('syncCalendars')->twice();
    $service->shouldReceive('syncEvents')->twice();

    $job = new SyncGoogleCalendars();
    $job->handle($service);
});
