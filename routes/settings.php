<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\GoogleCalendarAuthController;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/google-calendar', 'pages::settings.google-calendar')
        ->name('google-calendar.edit');

    Route::get('settings/google-calendar/connect', [GoogleCalendarAuthController::class, 'connect'])
        ->name('google-calendar.connect');

    Route::get('settings/google-calendar/callback', [GoogleCalendarAuthController::class, 'callback'])
        ->name('google-calendar.callback');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');
});
