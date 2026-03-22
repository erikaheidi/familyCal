<?php

use App\Models\GoogleAccount;
use App\Services\GoogleCalendar\GoogleCalendarService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Google Calendar settings')] class extends Component {
    public ?GoogleAccount $account = null;

    /**
     * @var array<int, int>
     */
    public array $selectedCalendars = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->refreshAccount();
    }

    public function syncNow(GoogleCalendarService $calendarService): void
    {
        if (! $this->account) {
            return;
        }

        $calendarService->syncCalendars($this->account->fresh());
        $calendarService->syncEvents($this->account->fresh());
        $this->refreshAccount();

        $this->dispatch('google-calendar-synced');
    }

    public function saveSelections(): void
    {
        if (! $this->account) {
            return;
        }

        $selectedIds = array_map('intval', $this->selectedCalendars);

        $this->account->calendars()->update(['is_selected' => false]);

        if ($selectedIds !== []) {
            $this->account->calendars()
                ->whereIn('id', $selectedIds)
                ->update(['is_selected' => true]);
        }

        $this->dispatch('google-calendar-selection-saved');
    }

    public function disconnect(): void
    {
        if (! $this->account) {
            return;
        }

        $this->account->delete();
        $this->refreshAccount();

        $this->dispatch('google-calendar-disconnected');
    }

    #[Computed]
    public function calendars(): Collection
    {
        if (! $this->account) {
            return collect();
        }

        return $this->account->calendars()->orderBy('name')->get();
    }

    private function refreshAccount(): void
    {
        $this->account = Auth::user()->googleAccount()->first();
        $this->selectedCalendars = $this->account?->calendars()
            ->where('is_selected', true)
            ->pluck('id')
            ->all() ?? [];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Google Calendar settings') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Google Calendar')"
        :subheading="__('Connect your family calendar and choose which calendars to sync')"
    >
        @if (session('status') === 'google-calendar-connected')
            <flux:callout variant="success" class="mb-4">
                {{ __('Google Calendar connected successfully.') }}
            </flux:callout>
        @endif

        @if (session('error'))
            <flux:callout variant="danger" class="mb-4">
                {{ session('error') }}
            </flux:callout>
        @endif

        @if (! $this->account)
            <flux:text class="mb-4">
                {{ __('Connect a Google account to pull family events into this app.') }}
            </flux:text>

            <flux:button as="a" :href="route('google-calendar.connect')" variant="primary">
                {{ __('Connect Google Calendar') }}
            </flux:button>
        @else
            <flux:callout variant="subtle" class="mb-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="sm">{{ __('Connected account') }}</flux:heading>
                        <flux:text class="mt-1">{{ $this->account->email }}</flux:text>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button
                            size="sm"
                            variant="primary"
                            wire:click="syncNow"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Sync now') }}
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="disconnect"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Disconnect') }}
                        </flux:button>
                    </div>
                </div>
            </flux:callout>

            <form wire:submit="saveSelections" class="space-y-4">
                <div class="space-y-2">
                    <flux:heading size="sm">{{ __('Calendars to include') }}</flux:heading>

                    @forelse ($this->calendars as $calendar)
                        <div wire:key="calendar-{{ $calendar->id }}">
                            <flux:checkbox
                                wire:model="selectedCalendars"
                                value="{{ $calendar->id }}"
                                :label="$calendar->name"
                            />
                        </div>
                    @empty
                        <flux:text class="text-sm">
                            {{ __('No calendars found yet. Click Sync now to load them.') }}
                        </flux:text>
                    @endforelse
                </div>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">
                        {{ __('Save selection') }}
                    </flux:button>

                    <x-action-message on="google-calendar-selection-saved">
                        {{ __('Saved.') }}
                    </x-action-message>

                    <x-action-message on="google-calendar-synced">
                        {{ __('Sync completed.') }}
                    </x-action-message>

                    <x-action-message on="google-calendar-disconnected">
                        {{ __('Disconnected.') }}
                    </x-action-message>
                </div>
            </form>
        @endif
    </x-pages::settings.layout>
</section>
