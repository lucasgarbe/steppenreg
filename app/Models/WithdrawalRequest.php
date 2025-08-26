<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'token',
        'token_expires_at',
        'withdrawn_at',
        'withdrawal_reason',
        'is_withdrawn',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'is_withdrawn' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate token during creation
        static::creating(function ($withdrawalRequest) {
            if (!$withdrawalRequest->token) {
                $withdrawalRequest->token = $withdrawalRequest->generateUniqueToken();
                $withdrawalRequest->token_expires_at = now()->addDays(7);
            }
        });
    }

    // Relationships
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_withdrawn', true)->whereNotNull('withdrawn_at');
    }

    public function scopePending($query)
    {
        return $query->where('is_withdrawn', false)->whereNull('withdrawn_at');
    }

    public function scopeForTrack($query, int $trackId)
    {
        return $query->whereHas('registration', function ($q) use ($trackId) {
            $q->where('track_id', $trackId);
        });
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

    // Withdrawal Processing
    public function processWithdrawal(?string $reason = null): bool
    {
        if ($this->is_withdrawn || !$this->canWithdraw()) {
            return false;
        }

        $this->update([
            'is_withdrawn' => true,
            'withdrawn_at' => now(),
            'withdrawal_reason' => $reason ?? 'user_initiated',
        ]);

        // Update the related registration
        $this->registration?->update([
            'draw_status' => 'not_drawn', // Or keep original status based on business logic
        ]);

        return true;
    }

    public function canWithdraw(): bool
    {
        return !$this->is_withdrawn && 
               !$this->is_expired &&
               $this->registration?->draw_status === 'drawn' &&
               !$this->registration?->deleted_at;
    }

    public function getWithdrawUrl(): string
    {
        if (!$this->token) {
            $this->generateToken();
        }
        
        return route('withdraw.show', $this->token);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    public function getCanWithdrawAttribute(): bool
    {
        return $this->canWithdraw();
    }

    public function getStatusAttribute(): string
    {
        if ($this->is_withdrawn) {
            return 'completed';
        }
        
        if ($this->is_expired) {
            return 'expired';
        }
        
        return 'pending';
    }
}
