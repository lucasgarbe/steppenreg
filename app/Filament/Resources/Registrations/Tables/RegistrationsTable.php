<?php

namespace App\Filament\Resources\Registrations\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label('Age')
                    ->sortable(),

                TextColumn::make('track_name')
                    ->label('Track')
                    ->placeholder('No track selected')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('team.name')
                    ->label('Team')
                    ->placeholder('Individual')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('draw_status')
                    ->label('Draw Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'drawn' => 'success',
                        'waitlist' => 'warning',
                        'not_drawn' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'drawn' => 'Drawn',
                        'waitlist' => 'Waitlist',
                        'not_drawn' => 'Not Drawn',
                        default => $state,
                    })
                    ->sortable(),

                IconColumn::make('payed')
                    ->label('Paid')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('starting')
                    ->label('Starting')
                    ->boolean()
                    ->trueIcon('heroicon-o-play-circle')
                    ->falseIcon('heroicon-o-pause-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),

                TextColumn::make('finish_time')
                    ->label('Finish Time')
                    ->time('H:i')
                    ->placeholder('Not finished')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Finished' => 'success',
                        'Starting' => 'info',
                        'Paid' => 'warning',
                        'Drawn' => 'primary',
                        'Waitlist' => 'warning',
                        'Registered' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Registered At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('track')
                    ->label('Track')
                    ->form([
                        \Filament\Forms\Components\Select::make('track_id')
                            ->label('Select Track')
                            ->options(function () {
                                $tracks = app(\App\Settings\EventSettings::class)->tracks ?? [];
                                $options = [];
                                
                                foreach ($tracks as $track) {
                                    $label = $track['name'];
                                    if (isset($track['distance'])) {
                                        $label .= ' (' . $track['distance'] . ' km)';
                                    }
                                    $options[$track['id']] = $label;
                                }
                                
                                return $options;
                            })
                            ->placeholder('All tracks')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['track_id'], fn($query, $trackId) => $query->where('track_id', $trackId));
                    }),

                Filter::make('payed')
                    ->label('Paid Only')
                    ->query(fn(Builder $query): Builder => $query->where('payed', true)),

                Filter::make('starting')
                    ->label('Starting Only')
                    ->query(fn(Builder $query): Builder => $query->where('starting', true)),

                Filter::make('finished')
                    ->label('Finished Only')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('finish_time')),

                Filter::make('team_members')
                    ->label('Team Members Only')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('team_id')),

                Filter::make('individuals')
                    ->label('Individual Registrations')
                    ->query(fn(Builder $query): Builder => $query->whereNull('team_id')),

                Filter::make('drawn')
                    ->label('Drawn Only')
                    ->query(fn(Builder $query): Builder => $query->where('draw_status', 'drawn')),

                Filter::make('not_drawn')
                    ->label('Not Drawn Only')
                    ->query(fn(Builder $query): Builder => $query->where('draw_status', 'not_drawn')),

                Filter::make('waitlist')
                    ->label('Waitlist Only')
                    ->query(fn(Builder $query): Builder => $query->where('draw_status', 'waitlist')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update(['payed' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_starting')
                        ->label('Mark as Starting')
                        ->icon('heroicon-o-play-circle')
                        ->color('info')
                        ->action(fn(Collection $records) => $records->each->update(['starting' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_drawn')
                        ->label('Mark as Drawn')
                        ->icon('heroicon-o-star')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update([
                            'draw_status' => 'drawn',
                            'drawn_at' => now()
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_waitlist')
                        ->label('Mark as Waitlist')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(fn(Collection $records) => $records->each->update([
                            'draw_status' => 'waitlist',
                            'drawn_at' => now()
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_not_drawn')
                        ->label('Mark as Not Drawn')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(fn(Collection $records) => $records->each->update([
                            'draw_status' => 'not_drawn',
                            'drawn_at' => null
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
