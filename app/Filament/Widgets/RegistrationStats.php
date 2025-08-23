<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrationStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = \App\Models\Registration::getStats();
        
        return [
            Stat::make('Total Registrations', $stats['total'])
                ->description('All participants registered')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Paid Registrations', $stats['payed'])
                ->description($stats['unpayed'] . ' unpaid')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('Starting Participants', $stats['starting'])
                ->description('Confirmed to start')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('info'),
                
            Stat::make('Finished Participants', $stats['finished'])
                ->description('Completed the event')
                ->descriptionIcon('heroicon-m-flag')
                ->color('warning'),
        ];
    }
}
