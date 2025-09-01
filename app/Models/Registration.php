<?php

namespace App\Models;

use App\Settings\EventSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'participation_count',
        'age',
        'gender',
        'payed',
        'starting',
        'draw_status',
        'drawn_at',
        'starting_number',
        'finish_time',
        'notes',
        'promoted_from_waitlist_at',
    ];

    protected $casts = [
        'participation_count' => 'integer',
        'payed' => 'boolean',
        'starting' => 'boolean',
        'drawn_at' => 'datetime',
        'finish_time' => 'datetime:H:i',
        'promoted_from_waitlist_at' => 'datetime',
    ];

    // New Relationships
    public function waitlistEntry(): HasOne
    {
        return $this->hasOne(WaitlistEntry::class);
    }

    public function withdrawalRequest(): HasOne
    {
        return $this->hasOne(WithdrawalRequest::class);
    }

    // Existing Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

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
        return $query->whereHas('withdrawalRequest', function ($q) {
            $q->where('is_withdrawn', true);
        });
    }

    public function scopeWaitlistRegistered($query)
    {
        return $query->whereHas('waitlistEntry');
    }

    public function scopeCanJoinWaitlist($query)
    {
        return $query->where('draw_status', 'not_drawn')
            ->whereDoesntHave('waitlistEntry')
            ->whereDoesntHave('withdrawalRequest', function ($q) {
                $q->where('is_withdrawn', true);
            });
    }

    public function scopeCanWithdraw($query)
    {
        return $query->where('draw_status', 'drawn')
            ->whereDoesntHave('withdrawalRequest', function ($q) {
                $q->where('is_withdrawn', true);
            });
    }

    // Accessors - Updated to use new relationships where possible
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
        return $this->withdrawalRequest?->is_withdrawn ?? false;
    }

    public function getIsWaitlistRegisteredAttribute(): bool
    {
        return $this->draw_status === 'waitlist';
    }

    public function getCanJoinWaitlistAttribute(): bool
    {
        return $this->draw_status === 'not_drawn' &&
            !$this->is_withdrawn;
    }

    public function getCanWithdrawAttribute(): bool
    {
        return $this->draw_status === 'drawn' && !$this->is_withdrawn;
    }

    // Track and Gender Accessors (unchanged)
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
        return match ($this->gender) {
            'flinta' => __('messages.gender_flinta'),
            'all_gender' => __('messages.gender_all_gender'),
            default => null,
        };
    }

    // Starting Number Accessors (unchanged)
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

        if (
            isset($ranges['waitlist_overflow']) &&
            $this->starting_number >= $ranges['waitlist_overflow']['start'] &&
            $this->starting_number <= $ranges['waitlist_overflow']['end']
        ) {
            return 'waitlist_overflow';
        }

        return 'unknown';
    }

    public function getStartingNumberLabelAttribute(): ?string
    {
        if (!$this->starting_number) {
            return null;
        }

        return match ($this->starting_number_type) {
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
            'gender_flinta' => static::where('gender', 'flinta')->count(),
            'gender_all_gender' => static::where('gender', 'all_gender')->count(),
        ];
    }

    // Token Management Methods - Updated to use new relationships
    public function generateWaitlistToken(): string
    {
        if (!$this->waitlistEntry) {
            $this->waitlistEntry()->create([
                'registered_at' => now(),
                'original_draw_status' => $this->draw_status,
            ]);
            $this->load('waitlistEntry'); // Refresh the relationship
        }

        return $this->waitlistEntry->token;
    }

    public function generateWithdrawToken(): string
    {
        if (!$this->withdrawalRequest) {
            $this->withdrawalRequest()->create([]);
            $this->load('withdrawalRequest'); // Refresh the relationship
        }

        return $this->withdrawalRequest->token;
    }

    public function getWaitlistUrl(): string
    {
        if (!$this->waitlistEntry) {
            $this->generateWaitlistToken();
        }

        return $this->waitlistEntry->getWaitlistUrl();
    }

    public function getWithdrawUrl(): string
    {
        if (!$this->withdrawalRequest) {
            $this->generateWithdrawToken();
        }

        return $this->withdrawalRequest->getWithdrawUrl();
    }

    // Legacy Token Lookups - Updated to use new relationships
    public static function findByWaitlistToken(string $token): ?self
    {
        $waitlistEntry = WaitlistEntry::findByToken($token);
        return $waitlistEntry?->registration;
    }

    public static function findByWithdrawToken(string $token): ?self
    {
        $withdrawalRequest = WithdrawalRequest::findByToken($token);
        return $withdrawalRequest?->registration;
    }

    // Waitlist and Withdrawal Methods - Updated to use new relationships
    public function joinWaitlist(): bool
    {
        if (!$this->can_join_waitlist) {
            return false;
        }

        // Create waitlist entry (token will be auto-generated)
        if (!$this->waitlistEntry) {
            $this->waitlistEntry()->create([
                'registered_at' => now(),
                'original_draw_status' => $this->draw_status,
            ]);
            $this->load('waitlistEntry'); // Refresh the relationship
        }

        $this->update([
            'draw_status' => 'waitlist',
        ]);

        // Calculate position
        if ($this->waitlistEntry) {
            $this->waitlistEntry->calculatePosition();
        }

        return true;
    }

    public function withdraw(?string $reason = null): bool
    {
        if (!$this->can_withdraw) {
            return false;
        }

        // Create withdrawal request (token will be auto-generated)
        if (!$this->withdrawalRequest) {
            $this->withdrawalRequest()->create([]);
            $this->load('withdrawalRequest'); // Refresh the relationship
        }

        return $this->withdrawalRequest->processWithdrawal($reason);
    }

    public function getWaitlistPosition(): ?int
    {
        return $this->waitlistEntry?->calculatePosition();
    }
}
