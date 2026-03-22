<?php

use App\Models\CalendarEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function selectedCalendars(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        $account = $user->googleAccount;

        if (! $account) {
            return collect();
        }

        return $account->calendars()
            ->where('is_selected', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function upcomingEventsByCalendar(): Collection
    {
        $calendarIds = $this->selectedCalendars
            ->pluck('id')
            ->filter();

        if ($calendarIds->isEmpty()) {
            return collect();
        }

        return CalendarEvent::query()
            ->whereIn('google_calendar_id', $calendarIds)
            ->where(function ($query): void {
                $query
                    ->where('end_at', '>=', now())
                    ->orWhere(function ($query): void {
                        $query
                            ->whereNull('end_at')
                            ->where('start_at', '>=', now());
                    });
            })
            ->orderBy('start_at')
            ->get()
            ->groupBy('google_calendar_id')
            ->map(fn (Collection $events) => $events->take(4));
    }

    #[Computed]
    public function hasAccount(): bool
    {
        return Auth::user()?->googleAccount()->exists() ?? false;
    }
};
?>

<div class="contents">
    @if (! $this->hasAccount)
        <div class="flex h-full flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Upcoming events') }}</flux:heading>
                <flux:link class="text-xs" :href="route('google-calendar.edit')" wire:navigate>
                    {{ __('Manage') }}
                </flux:link>
            </div>

            <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('Connect your Google Calendar to see upcoming events here.') }}
            </flux:text>
        </div>
    @elseif ($this->selectedCalendars->isEmpty())
        <div class="flex h-full flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Upcoming events') }}</flux:heading>
                <flux:link class="text-xs" :href="route('google-calendar.edit')" wire:navigate>
                    {{ __('Manage') }}
                </flux:link>
            </div>

            <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('Select at least one calendar to see upcoming events.') }}
            </flux:text>
        </div>
    @else
        @foreach ($this->selectedCalendars as $calendar)
            @php
                $events = $this->upcomingEventsByCalendar->get($calendar->id, collect());
            @endphp

            <div class="flex h-full flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900" wire:key="calendar-widget-{{ $calendar->id }}">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ $calendar->name }}</flux:heading>
                    <flux:link class="text-xs" :href="route('google-calendar.edit')" wire:navigate>
                        {{ __('Manage') }}
                    </flux:link>
                </div>

                @if ($events->isEmpty())
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('No upcoming events found for this calendar.') }}
                    </flux:text>
                @else
                    <div class="flex flex-col">
                        @foreach ($events as $event)
                            <div
                                @class([
                                    'flex items-start justify-between gap-3 px-3 py-2',
                                    'bg-blue-50 dark:bg-blue-950/40' => $loop->odd,
                                    'bg-purple-50 dark:bg-purple-950/40' => $loop->even,
                                ])
                                wire:key="event-{{ $event->id }}"
                            >
                                <div class="min-w-0">
                                    <flux:heading size="xs" class="truncate">
                                        {{ $event->summary ?: __('(Untitled event)') }}
                                    </flux:heading>
                                </div>

                                <div class="text-right">
                                    <flux:text class="text-xs text-zinc-700 dark:text-zinc-200">
                                        @if ($event->is_all_day)
                                            {{ $event->start_at?->format('M j, Y') }}
                                        @else
                                            {{ $event->start_at?->format('M j, g:i A') }}
                                        @endif
                                    </flux:text>
                                    @if ($event->html_link)
                                        <flux:link class="text-xs" :href="$event->html_link" target="_blank">
                                            {{ __('Open') }}
                                        </flux:link>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</div>