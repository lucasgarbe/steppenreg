<?php

namespace App\Domain\StartingNumber\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bib extends Model
{
    protected $fillable = [
        'number',
        'tag_id',
    ];

    protected $casts = [
        'number' => 'integer',
        'tag_id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($bib) {
            // Clear any relationship cache when tag_id is updated
            // This ensures all related StartingNumber records see the new tag_id
            $bib->startingNumbers()->each(function ($sn) {
                $sn->refresh();
            });
        });

        static::deleting(function ($bib) {
            $count = $bib->startingNumbers()->count();
            if ($count > 0) {
                throw new \Exception("Cannot delete bib #{$bib->number} - it is assigned to {$count} participant(s). Clear assignments first.");
            }
        });
    }

    /**
     * All assignment records for this physical bib across participants.
     */
    public function startingNumbers(): HasMany
    {
        return $this->hasMany(StartingNumber::class);
    }
}
