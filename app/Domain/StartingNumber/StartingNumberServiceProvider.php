<?php

namespace App\Domain\StartingNumber;

use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\StartingNumber\Listeners\AssignStartingNumberOnDrawn;
use App\Domain\StartingNumber\Services\StartingNumberService;
use Filament\Panel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class StartingNumberServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register service singleton
        $this->app->singleton(StartingNumberService::class);

        // Merge domain configuration
        $this->mergeConfigFrom(
            __DIR__.'/config/starting-numbers.php',
            'starting-numbers'
        );

        // Register Filament plugin for admin panel - must be in register()
        try {
            Panel::configureUsing(function (Panel $panel): void {
                \Illuminate\Support\Facades\Log::info('StartingNumberServiceProvider: Panel::configureUsing callback executed', [
                    'panel_id' => $panel->getId(),
                ]);

                if ($panel->getId() === 'admin') {
                    $panel->plugin(StartingNumberPlugin::make());
                    \Illuminate\Support\Facades\Log::info('StartingNumberServiceProvider: Plugin registered on admin panel');
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('StartingNumberServiceProvider: Error in Panel::configureUsing', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function boot(): void
    {
        // Check feature toggle from main config
        if (! config('steppenreg.features.starting_numbers', true)) {
            return;
        }

        // Publish domain configuration
        $this->publishes([
            __DIR__.'/config/starting-numbers.php' => config_path('starting-numbers.php'),
        ], 'starting-numbers-config');

        // Register event listener
        Event::listen(
            RegistrationDrawn::class,
            AssignStartingNumberOnDrawn::class
        );
    }
}
