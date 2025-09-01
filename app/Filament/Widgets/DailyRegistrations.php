<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DailyRegistrations extends ChartWidget
{
    protected ?string $heading = 'Daily Registration Activity';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 4;

    public ?string $filter = '7days';

    protected function getData(): array
    {
        $filter = $this->filter;

        // Get date range based on filter
        $endDate = now()->endOfDay();
        $startDate = match ($filter) {
            '7days' => now()->subDays(7)->startOfDay(),
            '14days' => now()->subDays(14)->startOfDay(),
            '30days' => now()->subDays(30)->startOfDay(),
            'all' => Registration::min('created_at') ? Carbon::parse(Registration::min('created_at'))->startOfDay() : now()->subMonth()->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        // Generate date labels
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('M j');
            $current->addDay();
        }

        // Get daily registration counts
        $dailyData = Registration::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill data array with daily counts
        $dataPoints = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateString = $current->format('Y-m-d');
            $dayData = $dailyData->get($dateString);
            $dayCount = $dayData ? $dayData->count : 0;
            $dataPoints[] = $dayCount;
            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daily Registrations',
                    'data' => $dataPoints,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                ]
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '14days' => 'Last 14 days',
            '30days' => 'Last 30 days',
            'all' => 'All time',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Number of new registrations per day'
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Registrations'
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date'
                    ]
                ]
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
