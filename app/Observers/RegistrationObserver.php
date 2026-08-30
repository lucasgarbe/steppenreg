<?php

namespace App\Observers;

use App\Domain\Mail\Jobs\SendRegistrationConfirmation;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;

class RegistrationObserver
{
    public function created(Registration $registration): void
    {
        Log::info('[Mail Trail] RegistrationObserver::created fired', [
            'registration_id' => $registration->id,
            'email' => $registration->email,
            'name' => $registration->name,
        ]);

        SendRegistrationConfirmation::dispatch($registration);

        Log::info('[Mail Trail] SendRegistrationConfirmation job dispatched', [
            'registration_id' => $registration->id,
        ]);
    }
}
