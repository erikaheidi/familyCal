<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'google_account_id',
    'google_calendar_id',
    'name',
    'description',
    'timezone',
    'access_role',
    'background_color',
    'foreground_color',
    'is_primary',
    'is_selected',
])]
class GoogleCalendar extends Model
{
    /** @use HasFactory<\Database\Factories\GoogleCalendarFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_selected' => 'boolean',
        ];
    }

    public function googleAccount(): BelongsTo
    {
        return $this->belongsTo(GoogleAccount::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function syncState(): HasOne
    {
        return $this->hasOne(CalendarSyncState::class);
    }
}
