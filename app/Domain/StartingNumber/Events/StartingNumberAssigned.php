<?php

namespace App\Domain\StartingNumber\Events;

use App\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartingNumberAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Registration $registration,
        public int $number
    ) {}
}
