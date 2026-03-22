<?php

use App\Models\CalendarEvent;
use App\Models\GoogleAccount;
use App\Models\GoogleCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $view = 'week';
    public ?string $cursorDate = null;

    public function setView(string $view): void
    {
        if (! in_array($view, ['week', 'month'], true)) {
            return;
        }

        if ($this->view === $view) {
            return;
        }

        $this->view = $view;
        $this->cursorDate = $this->baseDate->toDateString();
        $this->clearRangeCache();
    }

    public function previousPeriod(): void
    {
        if (! $this->publicAccountEmail) {
            return;
        }

        $baseDate = $this->baseDate;
        $target = $this->view === 'month'
            ? $baseDate->subMonthNoOverflow()
            : $baseDate->subWeek();

        $this->cursorDate = $target->toDateString();
        $this->clearRangeCache();
    }

    public function nextPeriod(): void
    {
        if (! $this->publicAccountEmail) {
            return;
        }

        $baseDate = $this->baseDate;
        $target = $this->view === 'month'
            ? $baseDate->addMonthNoOverflow()
            : $baseDate->addWeek();

        $this->cursorDate = $target->toDateString();
        $this->clearRangeCache();
    }

    public function goToToday(): void
    {
        if (! $this->publicAccountEmail) {
            return;
        }

        $this->cursorDate = CarbonImmutable::now()->toDateString();
        $this->clearRangeCache();
    }

    private function clearRangeCache(): void
    {
        unset(
            $this->baseDate,
            $this->rangeStart,
            $this->rangeEnd,
            $this->rangeLabel,
            $this->days,
            $this->events,
            $this->eventsByDate,
        );
    }

    #[Computed]
    public function publicAccountEmail(): ?string
    {
        $email = config('services.public_calendar.account_email');

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return $email;
    }

    #[Computed]
    public function baseDate(): CarbonImmutable
    {
        if (is_string($this->cursorDate) && $this->cursorDate !== '') {
            return CarbonImmutable::parse($this->cursorDate);
        }

        return CarbonImmutable::now();
    }

    #[Computed]
    public function account(): ?GoogleAccount
    {
        if (! $this->publicAccountEmail) {
            return null;
        }

        return GoogleAccount::query()
            ->where('email', $this->publicAccountEmail)
            ->first();
    }

    #[Computed]
    public function selectedCalendars(): Collection
    {
        if (! $this->account) {
            return collect();
        }

        return $this->account->calendars()
            ->where('is_selected', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function rangeStart(): CarbonImmutable
    {
        $baseDate = $this->baseDate;

        if ($this->view === 'month') {
            return $baseDate->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        }

        return $baseDate->startOfWeek(CarbonImmutable::MONDAY);
    }

    #[Computed]
    public function rangeEnd(): CarbonImmutable
    {
        $baseDate = $this->baseDate;

        if ($this->view === 'month') {
            return $baseDate->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        }

        return $baseDate->endOfWeek(CarbonImmutable::SUNDAY);
    }

    #[Computed]
    public function days(): Collection
    {
        $days = collect();
            $current = $this->rangeStart;
        $end = $this->rangeEnd;

        while ($current->lte($end)) {
                $days->push($current);
                $current = $current->addDay();
        }

        return $days;
    }

    #[Computed]
    public function events(): Collection
    {
        if ($this->selectedCalendars->isEmpty()) {
            return collect();
        }

            $rangeStart = $this->rangeStart->startOfDay();
            $rangeEnd = $this->rangeEnd->endOfDay();
        $calendarIds = $this->selectedCalendars->pluck('id');

        return CalendarEvent::query()
            ->whereIn('google_calendar_id', $calendarIds)
            ->where(function ($query) use ($rangeStart, $rangeEnd): void {
                $query
                    ->whereBetween('start_at', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('end_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($query) use ($rangeStart, $rangeEnd): void {
                        $query
                            ->whereNotNull('end_at')
                            ->where('start_at', '<=', $rangeStart)
                            ->where('end_at', '>=', $rangeEnd);
                    });
            })
            ->orderBy('start_at')
            ->with('googleCalendar')
            ->get();
    }

    #[Computed]
    public function eventsByDate(): Collection
    {
        return $this->events
            ->filter(fn (CalendarEvent $event) => $event->start_at !== null)
            ->groupBy(fn (CalendarEvent $event) => $event->start_at->toDateString());
    }

    #[Computed]
    public function calendarColors(): array
    {
        $palette = [
            ['bg' => '#FDE68A', 'fg' => '#92400E'],
            ['bg' => '#BFDBFE', 'fg' => '#1E3A8A'],
            ['bg' => '#A7F3D0', 'fg' => '#065F46'],
            ['bg' => '#FBCFE8', 'fg' => '#9D174D'],
            ['bg' => '#FDBA74', 'fg' => '#7C2D12'],
            ['bg' => '#C4B5FD', 'fg' => '#4C1D95'],
        ];

        return $this->selectedCalendars
            ->values()
            ->mapWithKeys(function (GoogleCalendar $calendar, int $index) use ($palette): array {
                $fallback = $palette[$index % count($palette)];

                return [
                    $calendar->id => [
                        'bg' => $calendar->background_color ?: $fallback['bg'],
                        'fg' => $calendar->foreground_color ?: $fallback['fg'],
                    ],
                ];
            })
            ->all();
    }

    #[Computed]
    public function rangeLabel(): string
    {
        if ($this->view === 'month') {
            return 'Month of '.$this->baseDate->format('F Y');
        }

        return 'Week of '.$this->rangeStart->format('M j, Y');
    }
}; ?>

<section class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-500">FamilyCal</p>
            <h1 class="text-4xl font-semibold text-slate-900 dark:text-white sm:text-5xl">
                Family calendar
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-300">{{ $this->rangeLabel }}</p>
        </div>

        @php
            $navDisabled = ! $this->publicAccountEmail;
        @endphp

        <div class="flex flex-wrap items-center gap-3">
            <div class="inline-flex rounded-full border border-slate-200 bg-white/80 p-1 text-sm font-semibold text-slate-500 shadow-sm dark:border-white/10 dark:bg-white/10 dark:text-slate-200">
                <button
                    type="button"
                    wire:click="previousPeriod"
                    wire:loading.attr="disabled"
                    wire:target="previousPeriod,nextPeriod,goToToday,setView"
                    @disabled($navDisabled)
                    @class([
                        'rounded-full px-4 py-2 transition',
                        'hover:text-slate-900 dark:hover:text-white' => ! $navDisabled,
                        'cursor-not-allowed opacity-50' => $navDisabled,
                    ])
                >
                    Prev
                </button>
                <button
                    type="button"
                    wire:click="goToToday"
                    wire:loading.attr="disabled"
                    wire:target="previousPeriod,nextPeriod,goToToday,setView"
                    @disabled($navDisabled)
                    @class([
                        'rounded-full px-4 py-2 transition',
                        'hover:text-slate-900 dark:hover:text-white' => ! $navDisabled,
                        'cursor-not-allowed opacity-50' => $navDisabled,
                    ])
                >
                    Today
                </button>
                <button
                    type="button"
                    wire:click="nextPeriod"
                    wire:loading.attr="disabled"
                    wire:target="previousPeriod,nextPeriod,goToToday,setView"
                    @disabled($navDisabled)
                    @class([
                        'rounded-full px-4 py-2 transition',
                        'hover:text-slate-900 dark:hover:text-white' => ! $navDisabled,
                        'cursor-not-allowed opacity-50' => $navDisabled,
                    ])
                >
                    Next
                </button>
            </div>

            <div class="inline-flex rounded-full border border-slate-200 bg-white/80 p-1 text-sm font-semibold text-slate-500 shadow-sm dark:border-white/10 dark:bg-white/10 dark:text-slate-200">
                <button
                    type="button"
                    wire:click="setView('week')"
                    wire:loading.attr="disabled"
                    wire:target="previousPeriod,nextPeriod,goToToday,setView"
                    @class([
                        'rounded-full px-5 py-2 transition',
                        'bg-amber-300 text-amber-950 shadow-sm' => $view === 'week',
                        'hover:text-slate-900 dark:hover:text-white' => $view !== 'week',
                    ])
                >
                    Week
                </button>
                <button
                    type="button"
                    wire:click="setView('month')"
                    wire:loading.attr="disabled"
                    wire:target="previousPeriod,nextPeriod,goToToday,setView"
                    @class([
                        'rounded-full px-5 py-2 transition',
                        'bg-amber-300 text-amber-950 shadow-sm' => $view === 'month',
                        'hover:text-slate-900 dark:hover:text-white' => $view !== 'month',
                    ])
                >
                    Month
                </button>
            </div>
        </div>
    </div>

    @if (! $this->publicAccountEmail)
        <div class="rounded-3xl border border-dashed border-amber-200 bg-white/80 p-8 text-center shadow-sm dark:border-amber-200/30 dark:bg-white/5">
            <h2 class="text-2xl font-semibold text-amber-700 dark:text-amber-200">Public calendar not configured</h2>
            <p class="mt-2 text-base text-slate-600 dark:text-slate-300">
                Add <span class="font-semibold">PUBLIC_CALENDAR_ACCOUNT_EMAIL</span> to your .env file to show events here.
            </p>
        </div>
    @elseif (! $this->account)
        <div class="rounded-3xl border border-dashed border-amber-200 bg-white/80 p-8 text-center shadow-sm dark:border-amber-200/30 dark:bg-white/5">
            <h2 class="text-2xl font-semibold text-amber-700 dark:text-amber-200">No public calendar found</h2>
            <p class="mt-2 text-base text-slate-600 dark:text-slate-300">
                We could not find a Google account for {{ $this->publicAccountEmail }}.
            </p>
        </div>
    @elseif ($this->selectedCalendars->isEmpty())
        <div class="rounded-3xl border border-dashed border-amber-200 bg-white/80 p-8 text-center shadow-sm dark:border-amber-200/30 dark:bg-white/5">
            <h2 class="text-2xl font-semibold text-amber-700 dark:text-amber-200">No calendars selected</h2>
            <p class="mt-2 text-base text-slate-600 dark:text-slate-300">
                Choose which calendars are shared in your Google Calendar settings.
            </p>
        </div>
    @else
        <div
            class="relative rounded-[32px] border border-white/60 bg-white/90 p-6 shadow-2xl backdrop-blur dark:border-white/10 dark:bg-[#14141d]"
            wire:loading.class="opacity-70"
            wire:target="previousPeriod,nextPeriod,goToToday,setView"
        >
            <div
                class="absolute inset-0 z-10 hidden items-center justify-center rounded-[32px] bg-white/70 text-slate-700 backdrop-blur-sm dark:bg-[#14141d]/70 dark:text-slate-100"
                wire:loading.flex
                wire:target="previousPeriod,nextPeriod,goToToday,setView"
            >
                <div class="flex items-center gap-3 rounded-full bg-white/90 px-5 py-3 text-sm font-semibold shadow-sm dark:bg-white/10">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-400 border-t-transparent"></span>
                    Refreshing calendar
                </div>
            </div>
            <div class="grid grid-cols-7 gap-3 text-center text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span>Fri</span>
                <span>Sat</span>
                <span>Sun</span>
            </div>

            <div class="mt-6 grid grid-cols-7 gap-4">
                @foreach ($this->days as $day)
                    @php
                        $dateKey = $day->toDateString();
                        $events = $this->eventsByDate->get($dateKey, collect());
                        $isToday = $day->isToday();
                    @endphp

                    <div
                        wire:key="day-{{ $dateKey }}"
                        @class([
                            'flex min-h-[10rem] flex-col gap-3 rounded-3xl border border-slate-100 bg-white/80 p-4 shadow-sm dark:border-white/10 dark:bg-white/5',
                            'ring-2 ring-amber-300/70' => $isToday,
                        ])
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-2xl font-semibold text-slate-900 dark:text-white">
                                {{ $day->format('j') }}
                            </div>
                            @if ($view === 'week')
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    {{ $day->format('D') }}
                                </div>
                            @endif
                        </div>

                        @if ($events->isEmpty())
                            <div class="mt-auto flex items-center gap-2 text-xs text-slate-400">
                                <span class="h-2 w-2 rounded-full bg-slate-200 dark:bg-white/20"></span>
                                Open space
                            </div>
                        @else
                            <div class="flex flex-col gap-2">
                                @foreach ($events as $event)
                                    @php
                                        $color = $this->calendarColors[$event->google_calendar_id] ?? ['bg' => '#FDE68A', 'fg' => '#92400E'];
                                        $timeLabel = $event->is_all_day ? 'All day' : $event->start_at?->format('g:i A');
                                    @endphp

                                    <div
                                        wire:key="event-{{ $event->id }}"
                                        class="rounded-2xl px-3 py-2 text-sm font-semibold shadow-sm"
                                        style="background-color: {{ $color['bg'] }}; color: {{ $color['fg'] }};"
                                    >
                                        <div class="flex flex-col gap-1">
                                            <span class="break-words text-pretty">
                                                {{ $event->summary ?: __('(Untitled event)') }}
                                            </span>
                                            @if ($timeLabel)
                                                <span class="text-xs font-medium">{{ $timeLabel }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($this->events->isEmpty())
                <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    No events yet for this view.
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3 text-sm">
                @foreach ($this->selectedCalendars as $calendar)
                    @php
                        $color = $this->calendarColors[$calendar->id] ?? ['bg' => '#FDE68A', 'fg' => '#92400E'];
                    @endphp
                    <div
                        wire:key="legend-{{ $calendar->id }}"
                        class="flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold"
                        style="background-color: {{ $color['bg'] }}; color: {{ $color['fg'] }};"
                    >
                        <span class="h-2 w-2 rounded-full bg-white/70"></span>
                        {{ $calendar->name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
