<?php

namespace App\Domain\StartingNumber\Filament\Resources;

use App\Domain\StartingNumber\Filament\Resources\StartingNumberResource\Pages\ListStartingNumbers;
use App\Domain\StartingNumber\Models\Bib;
use App\Domain\StartingNumber\Models\StartingNumber;
use App\Domain\StartingNumber\Services\StartingNumberService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                TextColumn::make('bib.number')
                    ->label('Bib')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (int $state): string => $service->formatNumber($state)),

                TextColumn::make('bib.tag_id')
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
                            'assigned' => $query->whereHas(
                                'bib',
                                fn (Builder $q) => $q->whereNotNull('tag_id')->where('tag_id', '!=', '')
                            ),
                            'missing' => $query->whereHas(
                                'bib',
                                fn (Builder $q) => $q->where(
                                    fn (Builder $inner) => $inner->whereNull('tag_id')->orWhere('tag_id', '')
                                )
                            ),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                // Edit the tag_id on the shared Bib record, not the StartingNumber row.
                // This ensures the same tag_id is visible for all participants sharing the bib.
                Action::make('edit_tag')
                    ->label('Edit Tag')
                    ->icon(Heroicon::OutlinedPencil)
                    ->fillForm(function (StartingNumber $record): array {
                        return [
                            'tag_id' => $record->bib?->tag_id,
                        ];
                    })
                    ->schema([
                        TextInput::make('number')
                            ->label('Bib Number')
                            ->default(fn (StartingNumber $record): string => $record->number ? $service->formatNumber($record->number) : '—')
                            ->disabled()
                            ->helperText('Starting numbers are assigned automatically and cannot be changed here.'),

                        TextInput::make('tag_id')
                            ->label('Tag ID')
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('The tag ID used for automated time tracking. This is fixed to the physical bib and shared across all participants who use it.'),
                    ])
                    ->action(function (StartingNumber $record, array $data): void {
                        $bib = $record->bib;

                        if (! $bib) {
                            return;
                        }

                        $newTagId = blank($data['tag_id']) ? null : $data['tag_id'];

                        $bib->update(['tag_id' => $newTagId]);

                        Notification::make()
                            ->title('Tag ID updated')
                            ->body(sprintf('Tag updated for bib #%s. All %d participant(s) using this bib will see the new tag.',
                                $bib->number,
                                $bib->startingNumbers()->count()))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('bib.number');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStartingNumbers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['registration.team', 'bib']);
    }
}
