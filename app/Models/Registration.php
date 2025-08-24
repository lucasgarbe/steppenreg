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
        'starting_number',
        'finish_time',
        'notes',
        'waitlist_token',
        'waitlist_token_expires_at',
        'withdraw_token',
        'withdraw_token_expires_at',
        'waitlist_registered_at',
        'withdrawn_at',
        'promoted_from_waitlist_at',
        'original_draw_status',
        'withdrawal_reason',
        'is_withdrawn',
    ];

    protected $casts = [
        'payed' => 'boolean',
        'starting' => 'boolean',
        'is_withdrawn' => 'boolean',
        'drawn_at' => 'datetime',
        'finish_time' => 'datetime:H:i',
        'waitlist_registered_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'promoted_from_waitlist_at' => 'datetime',
        'waitlist_token_expires_at' => 'datetime',
        'withdraw_token_expires_at' => 'datetime',
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

    public function scopeWithdrawn($query)
    {
        return $query->whereNotNull('withdrawn_at');
    }

    public function scopeWaitlistRegistered($query)
    {
        return $query->whereNotNull('waitlist_registered_at');
    }

    public function scopeCanJoinWaitlist($query)
    {
        return $query->where('draw_status', 'not_drawn')
                    ->whereNull('waitlist_registered_at')
                    ->whereNull('withdrawn_at');
    }

    public function scopeCanWithdraw($query)
    {
        return $query->where('draw_status', 'drawn')
                    ->whereNull('withdrawn_at');
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

    public function getIsWithdrawnAttribute(): bool
    {
        return !is_null($this->withdrawn_at);
    }

    public function getIsWaitlistRegisteredAttribute(): bool
    {
        return !is_null($this->waitlist_registered_at);
    }

    public function getCanJoinWaitlistAttribute(): bool
    {
        return $this->draw_status === 'not_drawn' && 
               !$this->is_waitlist_registered && 
               !$this->is_withdrawn;
    }

    public function getCanWithdrawAttribute(): bool
    {
        return $this->draw_status === 'drawn' && !$this->is_withdrawn;
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
            'flinta' => __('messages.gender_flinta'),
            'all_gender' => __('messages.gender_all_gender'),
            default => null,
        };
    }

    public function getFormattedStartingNumberAttribute(): ?string
    {
        return $this->starting_number ? sprintf('%03d', $this->starting_number) : null;
    }

    public function getStartingNumberTypeAttribute(): ?string
    {
        if (!$this->starting_number || !$this->track_id) {
            return null;
        }

        $ranges = app(\App\Services\StartingNumberService::class)->getTrackRanges($this->track_id);
        
        if ($this->starting_number >= $ranges['main']['start'] && $this->starting_number <= $ranges['main']['end']) {
            return 'main';
        }
        
        if ($this->starting_number >= $ranges['waitlist']['start'] && $this->starting_number <= $ranges['waitlist']['end']) {
            return 'waitlist';
        }
        
        if (isset($ranges['waitlist_overflow']) && 
            $this->starting_number >= $ranges['waitlist_overflow']['start'] && 
            $this->starting_number <= $ranges['waitlist_overflow']['end']) {
            return 'waitlist_overflow';
        }
        
        return 'unknown';
    }

    public function getStartingNumberLabelAttribute(): ?string
    {
        if (!$this->starting_number) {
            return null;
        }

        return match($this->starting_number_type) {
            'main' => $this->formatted_starting_number,
            'waitlist' => $this->formatted_starting_number . ' (W)',
            'waitlist_overflow' => $this->formatted_starting_number . ' (W+)',
            default => $this->formatted_starting_number
        };
    }

    public static function getGenderOptions(): array
    {
        return [
            'flinta' => __('messages.gender_flinta'),
            'all_gender' => __('messages.gender_all_gender'),
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
            return __('messages.finished');
        }
        
        if ($this->is_starting) {
            return __('messages.starting');
        }
        
        if ($this->is_payed) {
            return __('messages.paid');
        }
        
        if ($this->is_drawn) {
            return __('messages.drawn');
        }
        
        if ($this->is_on_waitlist) {
            return __('messages.waitlist');
        }
        
        return __('messages.registered');
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
            'withdrawn' => static::withdrawn()->count(),
            'waitlist_registered' => static::waitlistRegistered()->count(),
        ];
    }

    // Token Management Methods
    public function generateWaitlistToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('waitlist_token', $token)->exists());

        $this->waitlist_token = $token;
        $this->waitlist_token_expires_at = now()->addDays(7);
        $this->save();

        return $token;
    }

    public function generateWithdrawToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('withdraw_token', $token)->exists());

        $this->withdraw_token = $token;
        $this->withdraw_token_expires_at = now()->addDays(7);
        $this->save();

        return $token;
    }

    public function getWaitlistUrl(): string
    {
        if (!$this->waitlist_token) {
            $this->generateWaitlistToken();
        }
        
        return route('waitlist.join', $this->waitlist_token);
    }

    public function getWithdrawUrl(): string
    {
        if (!$this->withdraw_token) {
            $this->generateWithdrawToken();
        }
        
        return route('withdraw.show', $this->withdraw_token);
    }

    public static function findByWaitlistToken(string $token): ?self
    {
        return static::where('waitlist_token', $token)->first();
    }

    public static function findByWithdrawToken(string $token): ?self
    {
        return static::where('withdraw_token', $token)->first();
    }

    public function joinWaitlist(): bool
    {
        if (!$this->can_join_waitlist) {
            return false;
        }

        $this->draw_status = 'waitlist';
        $this->waitlist_registered_at = now();
        $this->original_draw_status = 'not_drawn';
        
        return $this->save();
    }

    public function withdraw(?string $reason = null): bool
    {
        if (!$this->can_withdraw) {
            return false;
        }

        $this->original_draw_status = $this->draw_status;
        $this->is_withdrawn = true;
        $this->withdrawn_at = now();
        $this->withdrawal_reason = $reason ?? 'user_initiated';
        
        return $this->save();
    }

    public function getWaitlistPosition(): ?int
    {
        if ($this->draw_status !== 'waitlist' || !$this->waitlist_registered_at) {
            return null;
        }

        return static::where('track_id', $this->track_id)
                    ->where('draw_status', 'waitlist')
                    ->where('waitlist_registered_at', '<', $this->waitlist_registered_at)
                    ->count() + 1;
    }
}
