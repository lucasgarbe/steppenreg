<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Track extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'slug',
        'description',
        'capacity',
        'status',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    // Helper methods
    public function confirmedCount(): int
    {
        return $this->registrations()->where('draw_status', 'drawn')->count();
    }

    public function availableSpots(): int
    {
        return max(0, $this->capacity - $this->confirmedCount());
    }

    public function isFull(): bool
    {
        return $this->availableSpots() === 0;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && !$this->isFull();
    }
}
