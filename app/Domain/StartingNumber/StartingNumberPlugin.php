<?php

namespace App\Domain\StartingNumber;

use Filament\Contracts\Plugin;
use Filament\Panel;

class StartingNumberPlugin implements Plugin
{
    public function getId(): string
    {
        return 'starting-numbers';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        // Check if feature is enabled
        if (! config('starting-numbers.enabled', true)) {
            return;
        }

        $panel
            ->pages([
                \App\Domain\StartingNumber\Filament\Pages\ManageStartingNumbers::class,
            ])
            ->widgets([
                \App\Domain\StartingNumber\Filament\Widgets\StartingNumberStatsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot logic handled in Service Provider
    }
}
