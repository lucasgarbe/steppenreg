<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Settings\EventSettings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeamStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return ! app(EventSettings::class)->isLiveEvent();
    }

    protected function getStats(): array
    {
        $stats = Team::getStats();

        return [
            Stat::make('Total Teams', $stats['total'])
                ->description('Teams in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Teams with Members', $stats['with_members'])
                ->description('Teams that have participants')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Full Teams', $stats['full'])
                ->description('Teams at maximum capacity')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color('warning'),

            Stat::make('Avg Team Size', $stats['average_size'])
                ->description('Average members per team')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
