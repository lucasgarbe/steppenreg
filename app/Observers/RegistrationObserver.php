<?php

namespace App\Observers;

use App\Jobs\Mail\SendDrawNotification;
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
        if ($registration->wasChanged('draw_status') && $registration->draw_status !== 'not_drawn') {
            SendDrawNotification::dispatch($registration);
        }
    }
}