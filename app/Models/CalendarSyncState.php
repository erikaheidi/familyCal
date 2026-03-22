<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'google_calendar_id',
    'sync_token',
    'last_synced_at',
    'last_full_sync_at',
])]
class CalendarSyncState extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarSyncStateFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'last_full_sync_at' => 'datetime',
        ];
    }

    public function googleCalendar(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendar::class);
    }
}
