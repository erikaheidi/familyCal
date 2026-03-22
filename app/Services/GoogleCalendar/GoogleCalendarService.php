<?php

namespace App\Services\GoogleCalendar;

use App\Models\CalendarEvent;
use App\Models\CalendarSyncState;
use App\Models\GoogleAccount;
use App\Models\GoogleCalendar;
use Google\Service\Calendar\Event;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Calendar;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function __construct(private GoogleClientFactory $clientFactory)
    {
    }

    public function syncCalendars(GoogleAccount $account): void
    {
        $client = $this->clientFactory->make($account);
        $service = new Calendar($client);
        $pageToken = null;

        do {
            $calendarList = $service->calendarList->listCalendarList([
                'pageToken' => $pageToken,
            ]);

            foreach ($calendarList->getItems() as $calendarItem) {
                $calendar = GoogleCalendar::query()->firstOrNew([
                    'google_account_id' => $account->id,
                    'google_calendar_id' => (string) $calendarItem->getId(),
                ]);

                $isNew = ! $calendar->exists;

                $calendar->fill([
                    'name' => (string) $calendarItem->getSummary(),
                    'description' => $calendarItem->getDescription(),
                    'timezone' => $calendarItem->getTimeZone(),
                    'access_role' => $calendarItem->getAccessRole(),
                    'background_color' => $calendarItem->getBackgroundColor(),
                    'foreground_color' => $calendarItem->getForegroundColor(),
                    'is_primary' => (bool) $calendarItem->getPrimary(),
                ]);

                if ($isNew) {
                    $calendar->is_selected = (bool) $calendarItem->getPrimary();
                }

                $calendar->save();

                CalendarSyncState::query()->firstOrCreate([
                    'google_calendar_id' => $calendar->id,
                ]);
            }

            $pageToken = $calendarList->getNextPageToken();
        } while ($pageToken);

        $account->forceFill(['last_sync_at' => now()])->save();
    }

    public function syncEvents(GoogleAccount $account): void
    {
        $account->load(['calendars.syncState']);

        foreach ($account->calendars->where('is_selected', true) as $calendar) {
            $this->syncEventsForCalendar($calendar);
        }
    }

    public function upsertEvent(GoogleCalendar $calendar, Event $event): void
    {
        if ($event->getStatus() === 'cancelled') {
            CalendarEvent::query()
                ->where('google_calendar_id', $calendar->id)
                ->where('google_event_id', (string) $event->getId())
                ->delete();

            return;
        }

        [$startAt, $endAt, $isAllDay] = $this->resolveEventTimes($event);

        $payload = json_decode(json_encode($event), true);

        CalendarEvent::query()->updateOrCreate(
            [
                'google_calendar_id' => $calendar->id,
                'google_event_id' => (string) $event->getId(),
            ],
            [
                'status' => $event->getStatus(),
                'summary' => $event->getSummary(),
                'description' => $event->getDescription(),
                'location' => $event->getLocation(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'is_all_day' => $isAllDay,
                'html_link' => $event->getHtmlLink(),
                'recurrence' => $event->getRecurrence(),
                'payload' => $payload,
                'google_updated_at' => $event->getUpdated()
                    ? Carbon::parse($event->getUpdated())
                    : null,
            ]
        );
    }

    private function syncEventsForCalendar(GoogleCalendar $calendar): void
    {
        $account = $calendar->googleAccount;

        if (! $account) {
            return;
        }

        $client = $this->clientFactory->make($account);
        $service = new Calendar($client);
        $state = $calendar->syncState ?: CalendarSyncState::query()->create([
            'google_calendar_id' => $calendar->id,
        ]);

        $pageToken = null;
        $nextSyncToken = null;

        try {
            do {
                $parameters = [
                    'pageToken' => $pageToken,
                    'singleEvents' => true,
                    'showDeleted' => true,
                    'maxResults' => 2500,
                    'orderBy' => 'startTime',
                ];

                if ($state->sync_token) {
                    $parameters['syncToken'] = $state->sync_token;
                } else {
                    $parameters['timeMin'] = now()->subMonths(2)->toRfc3339String();
                    $parameters['timeMax'] = now()->addYear()->toRfc3339String();
                }

                $events = $service->events->listEvents($calendar->google_calendar_id, $parameters);

                foreach ($events->getItems() as $event) {
                    $this->upsertEvent($calendar, $event);
                }

                $pageToken = $events->getNextPageToken();
                $nextSyncToken = $events->getNextSyncToken() ?? $nextSyncToken;
            } while ($pageToken);
        } catch (GoogleServiceException $exception) {
            if ($exception->getCode() === 410 && $state->sync_token) {
                $state->forceFill(['sync_token' => null])->save();
                $this->syncEventsForCalendar($calendar);

                return;
            }

            throw $exception;
        }

        $state->forceFill([
            'sync_token' => $nextSyncToken ?? $state->sync_token,
            'last_synced_at' => now(),
            'last_full_sync_at' => $state->last_full_sync_at
                ?? ($state->sync_token ? null : now()),
        ])->save();
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: bool}
     */
    private function resolveEventTimes(Event $event): array
    {
        $start = $event->getStart();
        $end = $event->getEnd();

        $isAllDay = false;
        $startAt = null;
        $endAt = null;

        if ($start?->getDate()) {
            $isAllDay = true;
            $startAt = Carbon::parse($start->getDate())->startOfDay();
        } elseif ($start?->getDateTime()) {
            $startAt = Carbon::parse($start->getDateTime());
        }

        if ($end?->getDate()) {
            $endAt = Carbon::parse($end->getDate())->startOfDay();
        } elseif ($end?->getDateTime()) {
            $endAt = Carbon::parse($end->getDateTime());
        }

        return [$startAt, $endAt, $isAllDay];
    }
}
