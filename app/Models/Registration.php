<?php

namespace App\Models;

use EventSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'track_id',
        'team_id',
        'age',
        'payed',
        'starting',
        'finish_time',
        'notes',
    ];

    protected $casts = [
        'payed' => 'boolean',
        'starting' => 'boolean',
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
            return 'Payed';
        }
        
        return 'Registered';
    }

    // Static methods
    public static function getStats(): array
    {
        return [
            'total' => static::count(),
            'payed' => static::payed()->count(),
            'unpayed' => static::unpayed()->count(),
            'starting' => static::starting()->count(),
            'finished' => static::finished()->count(),
        ];
    }
}
