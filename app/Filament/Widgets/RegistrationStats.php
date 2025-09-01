<?php

namespace App\Filament\Widgets;

use App\Settings\EventSettings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Psy\Readline\Hoa\Event;

class RegistrationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected function getStats(): array
    {
        $stats = \App\Models\Registration::getStats();

        $statCards = [];

        array_push(
            $statCards,
            Stat::make('Total Registrations', $stats['total'])
                ->description('All participants registered')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
        );

        array_push(
            $statCards,
            Stat::make('FLINTA* Registrations', $stats['gender_flinta'])
                ->description(number_format($stats['gender_flinta'] / $stats['total'] * 100, 2, ',', '') . '%')
                ->descriptionIcon('heroicon-m-percent-badge')
                ->color('primary')
        );

        array_push(
            $statCards,
            Stat::make('All Gender Registrations', $stats['gender_all_gender'])
                ->description(number_format($stats['gender_all_gender'] / $stats['total'] * 100, 2, ',', '') . '%')
                ->descriptionIcon('heroicon-m-percent-badge')
                ->color('primary')
        );

        array_push(
            $statCards,
            Stat::make('Paid Registrations', $stats['payed'])
                ->description($stats['unpayed'] . ' unpaid')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
        );

        if (app(EventSettings::class)->application_state == 'live_event') {
            array_push(
                $statCards,
                Stat::make('Starting Participants', $stats['starting'])
                    ->description('Confirmed to start')
                    ->descriptionIcon('heroicon-m-play-circle')
                    ->color('info')
            );

            array_push(
                $statCards,
                Stat::make('Finished Participants', $stats['finished'])
                    ->description('Completed the event')
                    ->descriptionIcon('heroicon-m-flag')
                    ->color('warning'),
            );
        }

        return $statCards;
    }
}
