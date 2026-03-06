<?php

namespace App\Filament\Widgets;

use App\Settings\EventSettings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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

        $categories = app(EventSettings::class)->gender_categories;
        $locale = app()->getLocale();

        foreach ($categories as $category) {
            $key = 'gender_'.$category['key'];
            $count = $stats[$key] ?? 0;
            $percentage = $stats['total'] > 0 ? number_format($count / $stats['total'] * 100, 2, ',', '') : '0';
            $label = $category['translations'][$locale]['label'] ?? $category['key'];

            array_push(
                $statCards,
                Stat::make($label.' Registrations', $count)
                    ->description($percentage.'%')
                    ->descriptionIcon('heroicon-m-percent-badge')
                    ->color('primary')
            );
        }

        array_push(
            $statCards,
            Stat::make('Paid Registrations', $stats['payed'])
                ->description($stats['unpayed'].' unpaid but drawn')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->url('/admin/registrations?filters[payed][value]=unpaid&filters[draw_status][value]=drawn')
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
