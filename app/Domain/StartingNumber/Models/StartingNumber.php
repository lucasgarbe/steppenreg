<?php

namespace App\Domain\StartingNumber\Models;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StartingNumber extends Model
{
    protected $fillable = [
        'registration_id',
        'number',
        'tag_id',
    ];

    protected $casts = [
        'registration_id' => 'integer',
        'number' => 'integer',
        'tag_id' => 'string',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
