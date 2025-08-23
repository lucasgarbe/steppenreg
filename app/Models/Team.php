<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'max_members',
    ];

    protected $casts = [
        'max_members' => 'integer',
    ];

    protected $attributes = [
        'max_members' => 5,
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
