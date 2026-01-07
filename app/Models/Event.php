<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'description',
        'registration_opens_at',
        'registration_closes_at',
        'event_date',
        'settings',
    ];

    protected $casts = [
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'event_date' => 'date',
        'settings' => 'array',
    ];

    // Relationships
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
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
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canRegister(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        
        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at && $now->gte($this->registration_closes_at)) {
            return false;
        }

        return true;
    }

    // Gender category management
    public function getGenderCategories(): array
    {
        return $this->settings['gender_categories'] ?? $this->getDefaultGenderCategories();
    }

    public function getDefaultGenderCategories(): array
    {
        return [
            'flinta' => [
                'enabled' => true,
                'label' => 'FLINTA*',
                'registration_opens_at' => null,
            ],
            'all_gender' => [
                'enabled' => true,
                'label' => 'Open/All Gender',
                'registration_opens_at' => null,
            ],
        ];
    }

    public function isGenderCategoryOpen(string $gender): bool
    {
        $categories = $this->getGenderCategories();
        
        if (!isset($categories[$gender]) || !($categories[$gender]['enabled'] ?? false)) {
            return false;
        }
        
        $now = now();
        
        // Check event-level registration window
        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at && $now->gte($this->registration_closes_at)) {
            return false;
        }
        
        // Check gender-specific opening date
        $genderOpensAt = $categories[$gender]['registration_opens_at'] ?? null;
        if ($genderOpensAt && $now->lt(\Carbon\Carbon::parse($genderOpensAt))) {
            return false;
        }
        
        return true;
    }

    public function getGenderCategoryOpeningDate(string $gender): ?\Carbon\Carbon
    {
        $categories = $this->getGenderCategories();
        
        if (!isset($categories[$gender]['registration_opens_at'])) {
            return null;
        }
        
        $opensAt = $categories[$gender]['registration_opens_at'];
        return $opensAt ? \Carbon\Carbon::parse($opensAt) : null;
    }

    public function getAvailableGenderCategories(): array
    {
        return collect($this->getGenderCategories())
            ->filter(fn($category, $gender) => $this->isGenderCategoryOpen($gender))
            ->toArray();
    }

    public function getNextGenderCategoryOpening(): ?array
    {
        $now = now();
        $categories = $this->getGenderCategories();
        
        $nextOpening = null;
        
        foreach ($categories as $gender => $settings) {
            if (!($settings['enabled'] ?? false)) {
                continue;
            }
            
            $opensAt = isset($settings['registration_opens_at']) 
                ? \Carbon\Carbon::parse($settings['registration_opens_at']) 
                : null;
            
            if ($opensAt && $opensAt->gt($now)) {
                if (!$nextOpening || $opensAt->lt($nextOpening['datetime'])) {
                    $nextOpening = [
                        'gender' => $gender,
                        'label' => $settings['label'] ?? $gender,
                        'datetime' => $opensAt,
                    ];
                }
            }
        }
        
        return $nextOpening;
    }
}
