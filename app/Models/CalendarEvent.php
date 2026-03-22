<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'google_calendar_id',
    'google_event_id',
    'status',
    'summary',
    'description',
    'location',
    'start_at',
    'end_at',
    'is_all_day',
    'html_link',
    'recurrence',
    'payload',
    'google_updated_at',
])]
class CalendarEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarEventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
            'recurrence' => 'array',
            'payload' => 'array',
            'google_updated_at' => 'datetime',
        ];
    }

    public function googleCalendar(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendar::class);
    }
}
