<?php

namespace App\Observers;

use App\Jobs\Mail\SendRegistrationConfirmation;
use App\Models\Registration;

class RegistrationObserver
{
    public function created(Registration $registration): void
    {
        SendRegistrationConfirmation::dispatch($registration);
    }
}
