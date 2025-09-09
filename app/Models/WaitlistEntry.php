<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'token',
        'token_expires_at',
        'registered_at',
        'original_draw_status',
        'is_team_captain',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'registered_at' => 'datetime',
        'is_team_captain' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate token during creation
        static::creating(function ($waitlistEntry) {
            if (!$waitlistEntry->token) {
                $waitlistEntry->token = $waitlistEntry->generateUniqueToken();
                $waitlistEntry->token_expires_at = now()->addDays(7);
            }
        });
    }

    // Relationships
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereHas('registration', function ($q) {
            $q->where('draw_status', 'waitlist')
              ->whereNull('deleted_at');
        });
    }

    public function scopeForTrack($query, int $trackId)
    {
        return $query->whereHas('registration', function ($q) use ($trackId) {
            $q->where('track_id', $trackId);
        });
    }

    public function scopeOrderedByRegistration($query)
    {
        return $query->orderBy('registered_at');
    }

    public function scopeTeamCaptains($query)
    {
        return $query->where('is_team_captain', true);
    }

    public function scopeIndividuals($query)
    {
        return $query->where('is_team_captain', false)->orWhereNull('is_team_captain');
    }

    // Token Management
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }

    public function generateToken(): string
    {
        $this->token = $this->generateUniqueToken();
        $this->token_expires_at = now()->addDays(7);
        $this->save();

        return $this->token;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    // Team Management
    public function getTeamMembers()
    {
        if (!$this->registration->team_id) {
            return collect([$this->registration]);
        }

        return $this->registration->team->registrations;
    }

    public function isTeamEntry(): bool
    {
        return $this->registration?->team_id !== null;
    }

    public function getWaitlistUrl(): string
    {
        if (!$this->token) {
            $this->generateToken();
        }
        
        return route('waitlist.join', $this->token);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    public function getCanJoinAttribute(): bool
    {
        return !$this->is_expired && 
               $this->registration?->draw_status === 'not_drawn' &&
               !$this->registration?->is_withdrawn;
    }
}
