<?php

namespace App\Filament\Resources\Teams\Tables;

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

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Team Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('registrations_count')
                    ->label('Members')
                    ->counts('registrations')
                    ->sortable(),

                TextColumn::make('max_members')
                    ->label('Max Members')
                    ->sortable(),

                TextColumn::make('available_spots')
                    ->label('Available Spots')
                    ->state(function ($record): string {
                        $available = $record->getAvailableSpots();
                        return $available > 0 ? $available : 'Full';
                    })
                    ->color(function ($record): string {
                        return $record->getAvailableSpots() > 0 ? 'success' : 'danger';
                    }),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('full')
                    ->label('Full Teams')
                    ->query(fn(Builder $query): Builder => $query->whereRaw('(SELECT COUNT(*) FROM registrations WHERE team_id = teams.id AND deleted_at IS NULL) >= teams.max_members')),

                Filter::make('has_space')
                    ->label('Has Available Spots')
                    ->query(fn(Builder $query): Builder => $query->whereRaw('(SELECT COUNT(*) FROM registrations WHERE team_id = teams.id AND deleted_at IS NULL) < teams.max_members')),

                Filter::make('empty')
                    ->label('Empty Teams')
                    ->query(fn(Builder $query): Builder => $query->doesntHave('registrations')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
