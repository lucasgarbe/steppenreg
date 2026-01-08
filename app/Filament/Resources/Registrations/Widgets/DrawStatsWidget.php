<?php

namespace App\Filament\Resources\Registrations\Widgets;

use App\Models\Registration;
use App\Settings\EventSettings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DrawStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalRegistrations = Registration::count();
        $totalDrawn = Registration::drawn()->count();
        $totalNotDrawn = Registration::notDrawn()->count();

        return [
            Stat::make('Total Registrations', $totalRegistrations)
                ->color('primary'),

            Stat::make('Drawn', $totalDrawn)
                ->color('success'),

            Stat::make('Not Drawn', $totalNotDrawn)
                ->color('gray'),
        ];
    }
}

