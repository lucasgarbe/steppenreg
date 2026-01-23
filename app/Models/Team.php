<?php

namespace App\Models;

use App\Settings\EventSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($team) {
            $eventSettings = app(EventSettings::class);

            // If not enforcing same track, ensure name is globally unique
            if (! $eventSettings->enforce_same_track_for_teams && $team->isDirty('name')) {
                $existing = static::withoutTrashed()
                    ->where('name', $team->name)
                    ->where('id', '!=', $team->id)
                    ->exists();

                if ($existing) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], [], [])
                            ->errors()->add('name', "Team name '{$team->name}' already exists.")
                    );
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'max_members',
        'track_id',
    ];

    protected $casts = [
        'max_members' => 'integer',
        'track_id' => 'integer',
    ];

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
        // NULL max_members = unlimited capacity
        if ($this->max_members === null) {
            return false;
        }

        return $this->registrations()->count() >= $this->max_members;
    }

    public function getAvailableSpots(): int
    {
        // NULL max_members = unlimited capacity
        if ($this->max_members === null) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_members - $this->registrations()->count());
    }

    public function getMembersCount(): int
    {
        return $this->registrations()->count();
    }

    public function scopeNotFull($query)
    {
        // Teams with NULL max_members are always "not full"
        return $query->where(function ($q) {
            $q->whereNull('max_members')
                ->orWhereRaw('(SELECT COUNT(*) FROM registrations WHERE team_id = teams.id AND deleted_at IS NULL) < teams.max_members');
        });
    }

    public function scopeForTrack($query, $trackId)
    {
        // When coupling is disabled, return all teams
        if (! app(EventSettings::class)->enforce_same_track_for_teams) {
            return $query;
        }

        return $query->where('track_id', $trackId)->orWhereNull('track_id');
    }

    public function getTrackAttribute(): ?array
    {
        if (! $this->track_id) {
            return null;
        }

        $tracks = app(EventSettings::class)->tracks ?? [];

        return collect($tracks)->firstWhere('id', $this->track_id);
    }

    public function getTrackNameAttribute(): ?string
    {
        return $this->track['name'] ?? null;
    }

    private function shouldEnforceSameTrack(): bool
    {
        return app(EventSettings::class)->enforce_same_track_for_teams;
    }

    public function canAcceptRegistration($registration): bool
    {
        // Check if team is full
        if ($this->getIsFull()) {
            return false;
        }

        // Check track consistency (only if enforced)
        if ($this->shouldEnforceSameTrack()) {
            if ($this->track_id && $this->track_id !== $registration->track_id) {
                return false;
            }
        }

        return true;
    }

    public function addMember($registration): bool
    {
        if (! $this->canAcceptRegistration($registration)) {
            return false;
        }

        // Set team track from first member if not set AND enforcement is enabled
        if ($this->shouldEnforceSameTrack() && ! $this->track_id && $registration->track_id) {
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
            'full' => static::whereNotNull('max_members')
                ->whereRaw('(SELECT COUNT(*) FROM registrations WHERE team_id = teams.id AND deleted_at IS NULL) >= teams.max_members')
                ->count(),
            'with_members' => static::has('registrations')->count(),
            'average_size' => round(static::withCount('registrations')->get()->avg('registrations_count'), 1),
        ];
    }
}
