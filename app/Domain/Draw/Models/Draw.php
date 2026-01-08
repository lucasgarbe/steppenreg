<?php

namespace App\Domain\Draw\Models;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Draw extends Model
{
    protected $fillable = [
        'track_id',
        'executed_by_user_id',
        'executed_at',
        'total_registrations',
        'total_drawn',
        'total_not_drawn',
        'available_spots',
        'config',
        'notes',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'config' => 'array',
    ];

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function drawnRegistrations(): HasMany
    {
        return $this->hasMany(Registration::class)->where('draw_status', 'drawn');
    }

    public function notDrawnRegistrations(): HasMany
    {
        return $this->hasMany(Registration::class)->where('draw_status', 'not_drawn');
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_registrations === 0) {
            return 0;
        }
        
        return round(($this->total_drawn / $this->total_registrations) * 100, 2);
    }
}
