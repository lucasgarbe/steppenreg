<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use App\Settings\EventSettings;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegistrationTimelineByGender extends ChartWidget
{
    protected ?string $heading = 'Registration Timeline By Gender';
    
    protected int | string | array $columnSpan = 1;
    
    protected static ?int $sort = 3;
    
    public ?string $filter = '7days';
    
    public ?string $trackFilter = 'all';

    protected function getData(): array
    {
        $filter = $this->filter;
        $trackFilter = $this->trackFilter;
        
        // Get date range based on filter
        $endDate = now()->endOfDay();
        $startDate = match($filter) {
            '7days' => now()->subDays(7)->startOfDay(),
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

        // Build query for registration data grouped by gender and date
        $query = Registration::select(
            'gender',
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->whereNotNull('gender');
        
        // Apply track filter if not 'all'
        if ($trackFilter && $trackFilter !== 'all') {
            $query->where('track_id', $trackFilter);
        }
        
        $registrationData = $query
        ->groupBy('gender', 'date')
        ->orderBy('date')
        ->get()
        ->groupBy('gender');

        // Prepare datasets for each gender
        $datasets = [];
        $genderConfig = [
            'flinta' => [
                'label' => 'FLINTA*',
                'color' => 'rgb(139, 92, 246)',  // Purple
            ],
            'all_gender' => [
                'label' => 'All Gender',
                'color' => 'rgb(59, 130, 246)',  // Blue
            ]
        ];

        foreach ($genderConfig as $genderKey => $config) {
            $genderData = $registrationData->get($genderKey, collect());
            
            // Fill data array with daily counts
            $dataPoints = [];
            $current = $startDate->copy();
            $cumulativeCount = 0;
            
            while ($current <= $endDate) {
                $dateString = $current->format('Y-m-d');
                $dayData = $genderData->firstWhere('date', $dateString);
                $dayCount = $dayData ? $dayData->count : 0;
                
                $cumulativeCount += $dayCount;
                $dataPoints[] = $cumulativeCount;
                
                $current->addDay();
            }
            
            $datasets[] = [
                'label' => $config['label'],
                'data' => $dataPoints,
                'borderColor' => $config['color'],
                'backgroundColor' => $config['color'] . '20',
                'tension' => 0.3,
                'fill' => false,
                'pointBackgroundColor' => $config['color'],
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
    
    public function getTrackFilters(): array
    {
        $filters = ['all' => 'All Tracks'];
        
        $settings = app(EventSettings::class);
        
        if (isset($settings->tracks) && is_array($settings->tracks)) {
            foreach ($settings->tracks as $track) {
                $filters[(string)$track['id']] = $track['name'];
            }
        }
        
        return $filters;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('trackFilter')
                ->label($this->getTrackFilters()[$this->trackFilter] ?? 'All Tracks')
                ->color('gray')
                ->button()
                ->outlined()
                ->form([
                    Forms\Components\Select::make('trackFilter')
                        ->label('Track')
                        ->options($this->getTrackFilters())
                        ->default($this->trackFilter)
                ])
                ->action(function (array $data) {
                    $this->trackFilter = $data['trackFilter'];
                    $this->resetFilter();
                }),
        ];
    }
    
    protected function resetFilter(): void
    {
        // This will trigger a re-render of the chart
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
                    'text' => 'Cumulative registrations over time by gender category'
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Registrations'
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
