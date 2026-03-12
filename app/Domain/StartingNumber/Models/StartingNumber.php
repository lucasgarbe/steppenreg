<?php

namespace App\Domain\StartingNumber\Models;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StartingNumber extends Model
{
    protected $fillable = [
        'registration_id',
        'bib_id',
    ];

    protected $casts = [
        'registration_id' => 'integer',
        'bib_id' => 'integer',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function bib(): BelongsTo
    {
        return $this->belongsTo(Bib::class);
    }

    /**
     * Convenience accessor: the bib number for this assignment.
     */
    public function getNumberAttribute(): ?int
    {
        return $this->bib?->number;
    }

    /**
     * Convenience accessor: the tag_id of the physical bib.
     */
    public function getTagIdAttribute(): ?string
    {
        return $this->bib?->tag_id;
    }
}
