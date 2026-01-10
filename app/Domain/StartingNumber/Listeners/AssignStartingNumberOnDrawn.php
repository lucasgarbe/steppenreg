<?php

namespace App\Domain\StartingNumber\Listeners;

use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\StartingNumber\Events\StartingNumberAssigned;
use App\Domain\StartingNumber\Services\StartingNumberService;

class AssignStartingNumberOnDrawn
{
    public function __construct(
        private StartingNumberService $service
    ) {}

    public function handle(RegistrationDrawn $event): void
    {
        if (! config('steppenreg.features.starting_numbers', true)) {
            return;
        }

        $registration = $event->registration;

        if (! $registration->starting_number) {
            $number = $this->service->assignNumber($registration);

            if ($number) {
                $registration->updateQuietly(['starting_number' => $number]);
                event(new StartingNumberAssigned($registration, $number));
            }
        }
    }
}
