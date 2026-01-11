<?php

namespace App\Filament\Resources\Teams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
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

                TextColumn::make('track_id')
                    ->label('Track ID')
                    ->placeholder('No track')
                    ->sortable(),

                TextColumn::make('registrations_count')
                    ->label('Members')
                    ->counts('registrations')
                    ->sortable(),

                TextColumn::make('max_members')
                    ->label('Max Members')
                    ->sortable(),

                TextColumn::make('gender_ratio')
                    ->label('FLINTA*/All Gender')
                    ->getStateUsing(function ($record) {
                        $registrations = $record->registrations;
                        if ($registrations->isEmpty()) {
                            return '—';
                        }

                        $flintaCount = $registrations->where('gender', 'flinta')->count();
                        $allGenderCount = $registrations->where('gender', 'all_gender')->count();
                        $total = $registrations->count();

                        // Show as "F/A" format (e.g., "2/3" means 2 FLINTA*, 3 All Gender)
                        return "{$flintaCount}/{$allGenderCount}";
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === '—') {
                            return 'gray';
                        }

                        [$flinta, $allGender] = explode('/', $state);
                        $flintaCount = (int) $flinta;
                        $allGenderCount = (int) $allGender;
                        $total = $flintaCount + $allGenderCount;

                        if ($total === 0) {
                            return 'gray';
                        }

                        $flintaPercentage = ($flintaCount / $total) * 100;

                        // Color based on FLINTA* percentage
                        return match (true) {
                            $flintaPercentage >= 50 => 'purple',
                            $flintaPercentage >= 30 => 'info',
                            $flintaPercentage > 0 => 'warning',
                            default => 'gray'
                        };
                    })
                    ->tooltip(function ($record) {
                        $registrations = $record->registrations;
                        if ($registrations->isEmpty()) {
                            return null;
                        }

                        $flintaCount = $registrations->where('gender', 'flinta')->count();
                        $allGenderCount = $registrations->where('gender', 'all_gender')->count();
                        $total = $registrations->count();

                        if ($total === 0) {
                            return null;
                        }

                        $flintaPercentage = round(($flintaCount / $total) * 100, 1);
                        $allGenderPercentage = round(($allGenderCount / $total) * 100, 1);

                        return "FLINTA*: {$flintaCount} ({$flintaPercentage}%)\nAll Gender: {$allGenderCount} ({$allGenderPercentage}%)";
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withCount([
                            'registrations as flinta_count' => function ($query) {
                                $query->where('gender', 'flinta');
                            },
                        ])->orderBy('flinta_count', $direction);
                    }),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
