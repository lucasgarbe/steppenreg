<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'max_members',
        'track_id',
    ];

    protected $casts = [
        'max_members' => 'integer',
        'track_id' => 'integer',
    ];

    protected $attributes = [
        'max_members' => 5,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function getIsFull(): bool
    {
        return $this->registrations()->count() >= $this->max_members;
    }

    public function getAvailableSpots(): int
    {
        return max(0, $this->max_members - $this->registrations()->count());
    }

    public function getMembersCount(): int
    {
        return $this->registrations()->count();
    }

    public function scopeNotFull($query)
    {
        return $query->whereRaw('(SELECT COUNT(*) FROM registrations WHERE team_id = teams.id AND deleted_at IS NULL) < teams.max_members');
    }

    public function scopeForTrack($query, $trackId)
    {
        return $query->where('track_id', $trackId)->orWhereNull('track_id');
    }

    public function getTrackNameAttribute(): ?string
    {
        return $this->track?->name;
    }

    public function canAcceptRegistration($registration): bool
    {
        // Check if team is full
        if ($this->getIsFull()) {
            return false;
        }
        
        // Check track consistency
        if ($this->track_id && $this->track_id !== $registration->track_id) {
            return false;
        }
        
        return true;
    }

    public function addMember($registration): bool
    {
        if (!$this->canAcceptRegistration($registration)) {
            return false;
        }
        
        // Set team track from first member if not set
        if (!$this->track_id && $registration->track_id) {
            $this->track_id = $registration->track_id;
            $this->save();
        }
        
        $registration->team_id = $this->id;
        $registration->save();
        
        return true;
    }

    public static function getStats(): array
    {
        return [
            'total' => static::count(),
            'full' => static::whereRaw('(SELECT COUNT(*) FROM registrations WHERE team_id = teams.id AND deleted_at IS NULL) >= teams.max_members')->count(),
            'with_members' => static::has('registrations')->count(),
            'average_size' => round(static::withCount('registrations')->get()->avg('registrations_count'), 1),
        ];
    }
}
