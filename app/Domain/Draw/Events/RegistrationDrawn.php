<?php

namespace App\Domain\Draw\Events;

use App\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegistrationDrawn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Registration $registration
    ) {}
}
