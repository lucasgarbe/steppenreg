<?php

namespace App\Domain\StartingNumber\Models;

use App\Settings\EventSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrackStartingNumberRange extends Model
{
    protected $fillable = [
        'track_id',
        'range_start',
        'range_end',
        'overflow_start',
        'overflow_end',
        'label',
    ];

    protected $casts = [
        'track_id' => 'integer',
        'range_start' => 'integer',
        'range_end' => 'integer',
        'overflow_start' => 'integer',
        'overflow_end' => 'integer',
    ];

    public function scopeForTrack(Builder $query, int $trackId): Builder
    {
        return $query->where('track_id', $trackId);
    }

    /**
     * Resolve the track name from EventSettings (tracks live in JSON, not a DB table).
     */
    public function getTrackNameAttribute(): ?string
    {
        $settings = app(EventSettings::class);
        $track = collect($settings->tracks ?? [])->firstWhere('id', $this->track_id);

        return $track['name'] ?? null;
    }

    public function getMainCapacityAttribute(): int
    {
        return $this->range_end - $this->range_start + 1;
    }

    public function getOverflowCapacityAttribute(): int
    {
        return $this->overflow_end - $this->overflow_start + 1;
    }
}
