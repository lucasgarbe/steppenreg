<?php

namespace App\Domain\StartingNumber\Filament\Resources;

use App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource\Pages\CreateTrackStartingNumberRange;
use App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource\Pages\EditTrackStartingNumberRange;
use App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource\Pages\ListTrackStartingNumberRanges;
use App\Domain\StartingNumber\Models\TrackStartingNumberRange;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Settings\EventSettings;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TrackStartingNumberRangeResource extends Resource
{
    protected static ?string $model = TrackStartingNumberRange::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static string|UnitEnum|null $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Track Ranges';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'starting-number-ranges';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('track_id')
                    ->label('Track')
                    ->options(function () {
                        $settings = app(EventSettings::class);

                        return collect($settings->tracks ?? [])
                            ->mapWithKeys(fn ($track) => [$track['id'] => $track['name']])
                            ->toArray();
                    })
                    ->required()
                    ->unique(
                        table: 'track_starting_number_ranges',
                        column: 'track_id',
                        ignoreRecord: true
                    )
                    ->helperText('Each track can have only one number range configuration.'),

                TextInput::make('label')
                    ->label('Label')
                    ->nullable()
                    ->maxLength(255)
                    ->helperText('Optional human-readable label (e.g. "Short Course Block").'),

                TextInput::make('range_start')
                    ->label('Range Start')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('First number in the main range.'),

                TextInput::make('range_end')
                    ->label('Range End')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Last number in the main range. Must be greater than Range Start.'),

                TextInput::make('overflow_start')
                    ->label('Overflow Start')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('First number in the overflow block. Must be greater than Range End.'),

                TextInput::make('overflow_end')
                    ->label('Overflow End')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Last number in the overflow block. Must be greater than Overflow Start.'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('track_name')
                    ->label('Track')
                    ->getStateUsing(fn (TrackStartingNumberRange $record): string => $record->track_name ?? "Track {$record->track_id}")
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('track_id', $direction)),

                TextColumn::make('label')
                    ->label('Label')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('main_range')
                    ->label('Main Range')
                    ->getStateUsing(fn (TrackStartingNumberRange $record): string => "{$record->range_start} – {$record->range_end} ({$record->main_capacity} slots)"),

                TextColumn::make('overflow_range')
                    ->label('Overflow Range')
                    ->getStateUsing(fn (TrackStartingNumberRange $record): string => "{$record->overflow_start} – {$record->overflow_end} ({$record->overflow_capacity} slots)"),

                TextColumn::make('main_usage')
                    ->label('Main Used')
                    ->getStateUsing(function (TrackStartingNumberRange $record): string {
                        $status = app(StartingNumberService::class)->getRangeStatus($record->track_id);

                        return $status['configured']
                            ? "{$status['main']['used']} / {$status['main']['capacity']}"
                            : '—';
                    }),

                TextColumn::make('overflow_usage')
                    ->label('Overflow Used')
                    ->getStateUsing(function (TrackStartingNumberRange $record): string {
                        $status = app(StartingNumberService::class)->getRangeStatus($record->track_id);

                        return $status['configured']
                            ? "{$status['overflow']['used']} / {$status['overflow']['capacity']}"
                            : '—';
                    }),

                TextColumn::make('range_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (TrackStartingNumberRange $record): string {
                        $status = app(StartingNumberService::class)->getRangeStatus($record->track_id);

                        return $status['status'] ?? 'unknown';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'success',
                        'warning' => 'warning',
                        'critical', 'main_full' => 'danger',
                        'overflow_active' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'warning' => 'Warning (75%+)',
                        'critical' => 'Critical (90%+)',
                        'main_full' => 'Main Full',
                        'overflow_active' => 'Overflow Active',
                        default => ucfirst($state),
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('track_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackStartingNumberRanges::route('/'),
            'create' => CreateTrackStartingNumberRange::route('/create'),
            'edit' => EditTrackStartingNumberRange::route('/{record}/edit'),
        ];
    }
}
