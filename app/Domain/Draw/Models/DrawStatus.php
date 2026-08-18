<?php

namespace App\Domain\Draw\Models;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawStatus extends Model
{
    protected $table = 'draw_statuses';

    protected $fillable = [
        'registration_id',
        'status',
        'drawn_at',
    ];

    protected $casts = [
        'drawn_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
