<?php

use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('public calendar week navigation moves forward', function () {
    config()->set('services.public_calendar.account_email', 'public@example.com');

    Livewire::test('public.calendar')
        ->set('cursorDate', '2026-03-04')
        ->assertSee('Week of Mar 2, 2026')
        ->call('nextPeriod')
        ->assertSee('Week of Mar 9, 2026')
        ->call('previousPeriod')
        ->assertSee('Week of Mar 2, 2026');
});

test('public calendar month navigation moves forward', function () {
    config()->set('services.public_calendar.account_email', 'public@example.com');

    Livewire::test('public.calendar')
        ->set('cursorDate', '2026-01-15')
        ->call('setView', 'month')
        ->assertSee('Month of January 2026')
        ->call('nextPeriod')
        ->assertSee('Month of February 2026')
        ->call('previousPeriod')
        ->assertSee('Month of January 2026');
});

test('public calendar today button returns to current week', function () {
    config()->set('services.public_calendar.account_email', 'public@example.com');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-18'));

    Livewire::test('public.calendar')
        ->set('cursorDate', '2026-02-04')
        ->assertSee('Week of Feb 2, 2026')
        ->call('goToToday')
        ->assertSee('Week of Mar 16, 2026');

    CarbonImmutable::setTestNow();
});

test('public calendar switches from week to month view', function () {
    config()->set('services.public_calendar.account_email', 'public@example.com');

    Livewire::test('public.calendar')
        ->set('cursorDate', '2026-03-04')
        ->assertSee('Week of Mar 2, 2026')
        ->call('setView', 'month')
        ->assertSee('Month of March 2026');
});

    test('public calendar navigation is disabled when not configured', function () {
        config()->set('services.public_calendar.account_email', null);

        Livewire::test('public.calendar')
        ->assertSeeHtml('wire:click="previousPeriod"')
        ->assertSeeHtml('wire:click="goToToday"')
        ->assertSeeHtml('wire:click="nextPeriod"')
        ->assertSeeHtml('disabled');
    });