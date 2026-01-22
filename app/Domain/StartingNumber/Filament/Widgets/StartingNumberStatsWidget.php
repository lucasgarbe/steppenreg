<?php

namespace App\Domain\StartingNumber\Filament\Widgets;

use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StartingNumberStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        return Cache::remember('starting-numbers-stats', 300, function () {
            $config = config('starting-numbers', []);
            $tracks = $config['tracks'] ?? [];
            $overflow = $config['overflow'] ?? ['enabled' => false, 'start' => 9001, 'end' => 9999];

            // Calculate total capacity across all tracks
            $totalCapacity = 0;
            foreach ($tracks as $trackConfig) {
                $totalCapacity += ($trackConfig['end'] - $trackConfig['start'] + 1);
            }

            // Add overflow capacity if enabled
            if ($overflow['enabled'] ?? false) {
                $totalCapacity += ($overflow['end'] - $overflow['start'] + 1);
            }

            // Get total assigned numbers
            $totalAssigned = Registration::whereNotNull('starting_number')->count();

            // Calculate assignment rate
            $assignmentRate = $totalCapacity > 0 
                ? round(($totalAssigned / $totalCapacity) * 100, 1) 
                : 0;

            // Get last assignment timestamp
            $lastAssignment = Registration::whereNotNull('starting_number')
                ->orderBy('drawn_at', 'desc')
                ->first();

            $lastAssignedAt = $lastAssignment 
                ? $lastAssignment->drawn_at?->diffForHumans() 
                : 'Never';

            // Build stats array
            $stats = [];

            // Total assigned stat
            $stats[] = Stat::make('Total Assigned', $totalAssigned)
                ->description("{$totalCapacity} total capacity")
                ->descriptionIcon('heroicon-m-hashtag')
                ->color('success')
                ->chart($this->getAssignmentTrend());

            // Track breakdown stats
            $trackStats = $this->getTrackStats($tracks);
            foreach (array_slice($trackStats, 0, 2) as $trackStat) {
                $stats[] = $trackStat;
            }

            // Overflow stat
            if ($overflow['enabled'] ?? false) {
                $overflowUsed = $this->getOverflowCount(
                    $overflow['start'],
                    $overflow['end']
                );
                $overflowCapacity = $overflow['end'] - $overflow['start'] + 1;
                $overflowPercent = $overflowCapacity > 0
                    ? round(($overflowUsed / $overflowCapacity) * 100, 1)
                    : 0;

                $stats[] = Stat::make('Overflow Used', $overflowUsed)
                    ->description("{$overflowCapacity} available ({$overflowPercent}%)")
                    ->descriptionIcon('heroicon-m-inbox-stack')
                    ->color($overflowUsed > 0 ? 'warning' : 'gray');
            }

            // Last assignment stat
            $stats[] = Stat::make('Last Assigned', $lastAssignedAt)
                ->description('Most recent assignment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray');

            return $stats;
        });
    }

    protected function getTrackStats(array $tracks): array
    {
        $stats = [];

        foreach ($tracks as $trackId => $trackConfig) {
            $start = $trackConfig['start'];
            $end = $trackConfig['end'];
            $capacity = $end - $start + 1;

            $assigned = Registration::where('track_id', $trackId)
                ->whereNotNull('starting_number')
                ->count();

            $percentage = $capacity > 0 ? round(($assigned / $capacity) * 100, 1) : 0;

            $stats[] = Stat::make(
                $trackConfig['name'] ?? "Track {$trackId}",
                "{$assigned}/{$capacity}"
            )
                ->description("{$percentage}% used")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getColorForPercentage($percentage));
        }

        return $stats;
    }

    protected function getOverflowCount(int $start, int $end): int
    {
        return Registration::whereBetween('starting_number', [$start, $end])
            ->count();
    }

    protected function getColorForPercentage(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'danger';
        }

        if ($percentage >= 70) {
            return 'warning';
        }

        if ($percentage >= 40) {
            return 'success';
        }

        return 'gray';
    }

    protected function getAssignmentTrend(): array
    {
        // Get daily assignment counts for the last 7 days
        return Cache::remember('starting-numbers-trend', 300, function () {
            $days = 7;
            $data = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->startOfDay();
                $count = Registration::whereNotNull('starting_number')
                    ->whereDate('drawn_at', $date)
                    ->count();

                $data[] = $count;
            }

            return $data;
        });
    }

    public static function canView(): bool
    {
        return config('starting-numbers.enabled', true);
    }
}
