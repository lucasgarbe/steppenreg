<?php

namespace App\Domain\StartingNumber;

use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\StartingNumber\Listeners\AssignStartingNumberOnDrawn;
use App\Domain\StartingNumber\Services\StartingNumberService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class StartingNumberServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StartingNumberService::class);
    }

    public function boot(): void
    {
        if (! config('steppenreg.features.starting_numbers', true)) {
            return;
        }

        Event::listen(
            RegistrationDrawn::class,
            AssignStartingNumberOnDrawn::class
        );
    }
}
