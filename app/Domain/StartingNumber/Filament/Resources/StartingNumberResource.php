<?php

namespace App\Domain\StartingNumber\Filament\Resources;

use App\Domain\StartingNumber\Filament\Resources\StartingNumberResource\Pages\ListStartingNumbers;
use App\Domain\StartingNumber\Models\StartingNumber;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Settings\EventSettings;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StartingNumberResource extends Resource
{
    protected static ?string $model = StartingNumber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Starting Numbers';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'starting-numbers';

    public static function table(Table $table): Table
    {
        $service = app(StartingNumberService::class);

        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Bib')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (int $state): string => $service->formatNumber($state)),

                TextColumn::make('tag_id')
                    ->label('Tag ID')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Tag ID copied'),

                TextColumn::make('registration.name')
                    ->label('Participant')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('track_name')
                    ->label('Track')
                    ->getStateUsing(function (StartingNumber $record): string {
                        return $record->registration?->track_name ?? '—';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('registrations', 'starting_numbers.registration_id', '=', 'registrations.id')
                            ->orderBy('registrations.track_id', $direction);
                    }),

                TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('track')
                    ->label('Track')
                    ->options(function (): array {
                        $settings = app(EventSettings::class);

                        return collect($settings->tracks ?? [])
                            ->mapWithKeys(fn ($track) => [$track['id'] => $track['name']])
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas(
                            'registration',
                            fn (Builder $q) => $q->where('track_id', $data['value'])
                        );
                    }),

                Filter::make('tag_assigned')
                    ->label('Tag ID')
                    ->form([
                        \Filament\Forms\Components\Select::make('tag_status')
                            ->label('Tag Status')
                            ->options([
                                'assigned' => 'Tag ID assigned',
                                'missing' => 'Tag ID missing',
                            ])
                            ->placeholder('All'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['tag_status'] ?? null) {
                            'assigned' => $query->whereNotNull('tag_id')->where('tag_id', '!=', ''),
                            'missing' => $query->where(fn (Builder $q) => $q->whereNull('tag_id')->orWhere('tag_id', '')),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema([
                        TextInput::make('number')
                            ->label('Bib Number')
                            ->numeric()
                            ->disabled()
                            ->helperText('Starting numbers are assigned automatically and cannot be changed here.'),

                        TextInput::make('tag_id')
                            ->label('Tag ID')
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('The tag ID used for automated time tracking.'),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('number');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStartingNumbers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['registration.team']);
    }
}
