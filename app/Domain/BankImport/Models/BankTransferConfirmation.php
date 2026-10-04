<?php

namespace App\Domain\BankImport\Models;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransferConfirmation extends Model
{
    protected $fillable = [
        'registration_id',
        'fingerprint',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
