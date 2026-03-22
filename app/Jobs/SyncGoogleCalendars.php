<?php

namespace App\Jobs;

use App\Models\GoogleAccount;
use App\Services\GoogleCalendar\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleCalendars implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $calendarService): void
    {
        GoogleAccount::query()
            ->with('calendars.syncState')
            ->chunkById(50, function (Collection $accounts) use ($calendarService): void {
                foreach ($accounts as $account) {
                    $calendarService->syncCalendars($account);
                    $calendarService->syncEvents($account);
                }
            });
    }
}
