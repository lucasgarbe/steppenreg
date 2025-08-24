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
            // NOTE: Draw notifications are now sent manually via admin action
            // This prevents automatic emails during bulk draw operations
            
            // Assign starting number when status changes to 'drawn'
            if ($registration->draw_status === 'drawn' && !$registration->starting_number) {
                $startingNumberService = app(\App\Services\StartingNumberService::class);
                $number = $startingNumberService->assignNumber($registration);
                
                if ($number) {
                    // Use updateQuietly to prevent triggering observer again
                    $registration->updateQuietly(['starting_number' => $number]);
                }
            }
            
            // Clear starting number when no longer drawn (but not if withdrawn - they keep their number)
            if (($registration->draw_status === 'not_drawn' || $registration->draw_status === 'waitlist') && !$registration->is_withdrawn) {
                if ($registration->starting_number) {
                    $registration->updateQuietly(['starting_number' => null]);
                }
            }
        }
    }
}