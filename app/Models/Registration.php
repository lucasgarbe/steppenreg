<?php

namespace App\Models;

use App\Domain\Draw\Models\Draw;
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
        'draw_id',
        'age',
        'gender',
        'payed',
        'starting',
        'draw_status',
        'drawn_at',
        'starting_number',
        'finish_time',
        'notes',
        'custom_answers',
    ];

    protected $casts = [
        'payed' => 'boolean',
        'starting' => 'boolean',
        'drawn_at' => 'datetime',
        'finish_time' => 'datetime:H:i',
        'custom_answers' => 'array',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function draw(): BelongsTo
    {
        return $this->belongsTo(Draw::class);
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
        return ! is_null($this->finish_time);
    }

    public function getIsDrawnAttribute(): bool
    {
        return $this->draw_status === 'drawn';
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

    public function getGenderLabelAttribute(): string
    {
        $categories = app(EventSettings::class)->gender_categories;
        $category = collect($categories)->firstWhere('key', $this->gender);

        if (! $category) {
            return $this->gender ?? '';
        }

        $locale = app()->getLocale();

        return $category['translations'][$locale]['label'] ?? $category['key'];
    }

    public function getGenderColorAttribute(): string
    {
        $categories = app(EventSettings::class)->gender_categories;
        $category = collect($categories)->firstWhere('key', $this->gender);

        return $category['color'] ?? '#6b7280';
    }

    /**
     * Get gender options for FRONTEND registration (respects state restrictions)
     */
    public static function getGenderOptions(): array
    {
        $eventSettings = app(EventSettings::class);
        $availableCategories = $eventSettings->getAvailableGenderCategories();
        $locale = app()->getLocale();

        return collect($availableCategories)
            ->sortBy('sort_order')
            ->mapWithKeys(function ($category) use ($locale) {
                return [
                    $category['key'] => $category['translations'][$locale]['label'] ?? $category['key'],
                ];
            })
            ->toArray();
    }

    /**
     * Get ALL gender options for ADMIN panels (ignores state restrictions)
     */
    public static function getGenderOptionsForAdmin(): array
    {
        $eventSettings = app(EventSettings::class);
        $allCategories = $eventSettings->getAllGenderCategoriesForAdmin();
        $locale = app()->getLocale();

        return collect($allCategories)
            ->sortBy('sort_order')
            ->mapWithKeys(function ($category) use ($locale) {
                return [
                    $category['key'] => $category['translations'][$locale]['label'] ?? $category['key'],
                ];
            })
            ->toArray();
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

        return __('messages.registered');
    }

    // Custom answers helper methods
    public function getCustomAnswer(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_answers, $key, $default);
    }

    public function setCustomAnswer(string $key, mixed $value): void
    {
        $answers = $this->custom_answers ?? [];
        data_set($answers, $key, $value);
        $this->custom_answers = $answers;
    }

    // Static methods
    public static function getStats(): array
    {
        $stats = [
            'total' => static::count(),
            'drawn' => static::drawn()->count(),
            'not_drawn' => static::notDrawn()->count(),
            'payed' => static::payed()->count(),
            'unpayed' => static::unpayed()->count(),
            'starting' => static::starting()->count(),
            'finished' => static::finished()->count(),
        ];

        $categories = app(EventSettings::class)->gender_categories;
        foreach ($categories as $category) {
            $key = 'gender_'.$category['key'];
            $stats[$key] = static::where('gender', $category['key'])->count();
        }

        return $stats;
    }
}
