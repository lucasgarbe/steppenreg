<?php

namespace App\Filament\Resources\Registrations\Widgets;

use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrackStatsWidget extends BaseWidget
{
    protected static ?string $heading = 'Track Statistics';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('track_name')
                    ->label('Track')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('distance')
                    ->label('Distance')
                    ->suffix(' km')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_registrations')
                    ->label('Total')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('drawn_count')
                    ->label('Drawn')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('not_drawn_count')
                    ->label('Not Drawn')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('waitlist_count')
                    ->label('Waitlist')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('drawing_units')
                    ->label('Available Units')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->tooltip('Individual registrations + Team units available for draw'),
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        // Create a dummy model to satisfy the table requirement
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'track_statistics_widget';
            protected $fillable = ['*'];
            public $timestamps = false;
            protected $attributes = [];
        };

        return $model->newQuery();
    }

    public function getTableRecords(): \Illuminate\Support\Collection
    {
        $tracks = app(EventSettings::class)->tracks ?? [];
        $data = collect();

        // Create a model class for our data
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'track_statistics_widget';
            protected $fillable = ['*'];
            public $timestamps = false;
            protected $attributes = [];
        };

        foreach ($tracks as $track) {
            $stats = $this->calculateTrackStats($track['id']);

            // Create a proper model instance
            $model = new $modelClass;
            $model->id = $track['id'];
            $model->track_name = $track['name'];
            $model->distance = $track['distance'] ?? null;
            $model->total_registrations = $stats['total'];
            $model->drawn_count = $stats['drawn'];
            $model->waitlist_count = $stats['waitlist'];
            $model->not_drawn_count = $stats['not_drawn'];
            $model->drawing_units = $stats['drawing_units'];

            // Mark as existing so it doesn't try to save to database
            $model->exists = true;

            $data->push($model);
        }

        return $data;
    }

    private function calculateTrackStats(int $trackId): array
    {
        $registrations = Registration::where('track_id', $trackId);

        $total = $registrations->count();
        $drawn = $registrations->where('draw_status', 'drawn')->count();
        $waitlist = $registrations->where('draw_status', 'waitlist')->count();
        $notDrawn = $registrations->where('draw_status', 'not_drawn')->count();

        $individuals = $registrations->whereNull('team_id')->where('draw_status', 'not_drawn')->count();

        $teams = Team::where('track_id', $trackId)
            ->whereHas('registrations', function ($query) {
                $query->where('draw_status', 'not_drawn');
            })
            ->count();

        $drawingUnits = $individuals + $teams;

        return [
            'total' => $total,
            'drawn' => $drawn,
            'waitlist' => $waitlist,
            'not_drawn' => $notDrawn,
            'drawing_units' => $drawingUnits,
        ];
    }
}
