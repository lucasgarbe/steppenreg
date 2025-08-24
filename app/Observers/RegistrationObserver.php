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
        // Handle draw status changes
        if ($registration->wasChanged('draw_status')) {
            if ($registration->draw_status !== 'not_drawn') {
                SendDrawNotification::dispatch($registration);
            }
            
            // Assign starting number when status changes to 'drawn'
            if ($registration->draw_status === 'drawn' && !$registration->starting_number) {
                $startingNumberService = app(\App\Services\StartingNumberService::class);
                $number = $startingNumberService->assignNumber($registration);
                
                if ($number) {
                    $registration->starting_number = $number;
                    $registration->saveQuietly(); // Prevent infinite loop
                }
            }
            
            // Clear starting number when no longer drawn
            if ($registration->draw_status === 'not_drawn' || $registration->draw_status === 'waitlist') {
                if ($registration->starting_number) {
                    $registration->starting_number = null;
                    $registration->saveQuietly(); // Prevent infinite loop
                }
            }
        }
    }
}