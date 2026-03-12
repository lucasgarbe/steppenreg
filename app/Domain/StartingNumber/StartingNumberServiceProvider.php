<?php

namespace App\Domain\StartingNumber;

use App\Domain\StartingNumber\Filament\Resources\StartingNumberResource;
use App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource;
use App\Domain\StartingNumber\Services\StartingNumberService;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class StartingNumberServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StartingNumberService::class);

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel->resources([
                StartingNumberResource::class,
                TrackStartingNumberRangeResource::class,
            ]);
        });
    }

    public function boot(): void
    {
        //
    }
}
