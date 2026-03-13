<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use App\Settings\EventSettings;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class LiveEventTrackStats extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return app(EventSettings::class)->isLiveEvent();
    }

    public function getHeading(): string
    {
        return track_label().' Overview';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('track_name')
                    ->label(track_label())
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('admin.registrations.title'))
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('starting')
                    ->label(__('admin.registrations.columns.starting'))
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->paginated(false);
    }

    public function getTableRecords(): \Illuminate\Support\Collection
    {
        $tracks = app(EventSettings::class)->tracks ?? [];

        return collect($tracks)->map(function (array $track) {
            $trackId = $track['id'];

            $total = Registration::where('track_id', $trackId)->count();
            $starting = Registration::where('track_id', $trackId)->where('starting', true)->count();

            $model = new class extends Model
            {
                protected $table = 'live_event_track_stats_widget';

                public $timestamps = false;
            };

            $model->id = $trackId;
            $model->track_name = $track['name'];
            $model->total = $total;
            $model->starting = $starting;
            $model->exists = true;

            return $model;
        });
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $model = new class extends Model
        {
            protected $table = 'live_event_track_stats_widget';

            public $timestamps = false;
        };

        return $model->newQuery();
    }
}
