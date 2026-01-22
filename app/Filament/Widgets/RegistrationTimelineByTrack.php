<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use App\Settings\EventSettings;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegistrationTimelineByTrack extends ChartWidget
{
    public function getHeading(): string
    {
        return __('admin.registration_timeline_by_track');
    }

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    public ?string $filter = '7days';

    protected function getData(): array
    {
        $filter = $this->filter;

        // Get date range based on filter
        $endDate = now()->endOfDay();
        $startDate = match ($filter) {
            '7days' => now()->subDays(7)->startOfDay(),
            '30days' => now()->subDays(30)->startOfDay(),
            'all' => Registration::min('created_at') ? Carbon::parse(Registration::min('created_at'))->startOfDay() : now()->subMonth()->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        // Get tracks from settings
        $tracks = app(EventSettings::class)->tracks ?? [];
        $trackNames = collect($tracks)->pluck('name', 'id')->toArray();

        // Generate date labels
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('M j');
            $current->addDay();
        }

        // Get registration data grouped by track and date
        $registrationData = Registration::select(
            'track_id',
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('track_id')
            ->groupBy('track_id', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('track_id');

        // Prepare datasets for each track
        $datasets = [];
        $colors = [
            'rgb(59, 130, 246)',  // Blue
            'rgb(16, 185, 129)',  // Green
            'rgb(245, 158, 11)',  // Yellow
            'rgb(239, 68, 68)',   // Red
            'rgb(139, 92, 246)',  // Purple
            'rgb(236, 72, 153)',  // Pink
        ];

        foreach ($tracks as $index => $track) {
            $trackId = $track['id'];
            $trackName = $track['name'];
            $trackData = $registrationData->get($trackId, collect());

            // Fill data array with daily counts
            $dataPoints = [];
            $current = $startDate->copy();
            $cumulativeCount = 0;

            while ($current <= $endDate) {
                $dateString = $current->format('Y-m-d');
                $dayData = $trackData->firstWhere('date', $dateString);
                $dayCount = $dayData ? $dayData->count : 0;

                $cumulativeCount += $dayCount;
                $dataPoints[] = $cumulativeCount;

                $current->addDay();
            }

            $datasets[] = [
                'label' => $trackName,
                'data' => $dataPoints,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)].'20',
                'tension' => 0.3,
                'fill' => false,
                'pointBackgroundColor' => $colors[$index % count($colors)],
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            'all' => 'All time',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Cumulative registrations over time by '.strtolower(track_label()),
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Registrations',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
