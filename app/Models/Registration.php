<?php

namespace App\Models;

use App\Settings\EventSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        
        // Auto-leave team if track changes to maintain consistency
        static::updating(function ($registration) {
            if ($registration->isDirty('track_id') && $registration->team_id) {
                $team = $registration->team;
                if ($team && $team->track_id && $team->track_id !== $registration->track_id) {
                    $registration->team_id = null;
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'track_id',
        'team_id',
        'age',
        'gender',
        'payed',
        'starting',
        'draw_status',
        'drawn_at',
        'finish_time',
        'notes',
    ];

    protected $casts = [
        'payed' => 'boolean',
        'starting' => 'boolean',
        'drawn_at' => 'datetime',
        'finish_time' => 'datetime:H:i',
    ];

    // Scopes
    public function scopePayed($query)
    {
        return $query->where('payed', true);
    }

    public function scopeUnpayed($query)
    {
        return $query->where('payed', false);
    }

    public function scopeStarting($query)
    {
        return $query->where('starting', true);
    }

    public function scopeNotStarting($query)
    {
        return $query->where('starting', false);
    }

    public function scopeFinished($query)
    {
        return $query->whereNotNull('finish_time');
    }

    public function scopeNotFinished($query)
    {
        return $query->whereNull('finish_time');
    }

    public function scopeDrawn($query)
    {
        return $query->where('draw_status', 'drawn');
    }

    public function scopeNotDrawn($query)
    {
        return $query->where('draw_status', 'not_drawn');
    }

    public function scopeOnWaitlist($query)
    {
        return $query->where('draw_status', 'waitlist');
    }

    // Accessors
    public function getIsPayedAttribute(): bool
    {
        return $this->payed;
    }

    public function getIsStartingAttribute(): bool
    {
        return $this->starting;
    }

    public function getHasFinishedAttribute(): bool
    {
        return !is_null($this->finish_time);
    }

    public function getIsDrawnAttribute(): bool
    {
        return $this->draw_status === 'drawn';
    }

    public function getIsOnWaitlistAttribute(): bool
    {
        return $this->draw_status === 'waitlist';
    }

    public function getTrackAttribute(): ?array
    {
        if (!$this->track_id) {
            return null;
        }
        
        $tracks = app(EventSettings::class)->tracks ?? [];
        
        return collect($tracks)->firstWhere('id', $this->track_id);
    }

    public function getTrackNameAttribute(): ?string
    {
        return $this->track['name'] ?? null;
    }

    public function getGenderLabelAttribute(): ?string
    {
        return match($this->gender) {
            'flinta' => 'FLINTA*',
            'all_gender' => 'All Gender',
            default => null,
        };
    }

    public static function getGenderOptions(): array
    {
        return [
            'flinta' => 'FLINTA*',
            'all_gender' => 'All Gender',
        ];
    }

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getStatusAttribute(): string
    {
        if ($this->has_finished) {
            return 'Finished';
        }
        
        if ($this->is_starting) {
            return 'Starting';
        }
        
        if ($this->is_payed) {
            return 'Paid';
        }
        
        if ($this->is_drawn) {
            return 'Drawn';
        }
        
        if ($this->is_on_waitlist) {
            return 'Waitlist';
        }
        
        return 'Registered';
    }

    // Static methods
    public static function getStats(): array
    {
        return [
            'total' => static::count(),
            'drawn' => static::drawn()->count(),
            'not_drawn' => static::notDrawn()->count(),
            'waitlist' => static::onWaitlist()->count(),
            'payed' => static::payed()->count(),
            'unpayed' => static::unpayed()->count(),
            'starting' => static::starting()->count(),
            'finished' => static::finished()->count(),
        ];
    }
}
