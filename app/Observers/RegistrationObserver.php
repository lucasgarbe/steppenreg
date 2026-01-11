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

    public function updated(Registration $registration): void
    {
        // NOTE: Draw notifications are now sent manually via admin action
        // This prevents automatic emails during bulk draw operations

        // NOTE: Starting number assignment is now handled by the StartingNumber domain
        // via event listeners (AssignStartingNumberOnDrawn listens to RegistrationDrawn)
    }
}
