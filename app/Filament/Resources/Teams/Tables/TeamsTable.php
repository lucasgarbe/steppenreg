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
                    ->label(track_label())
                    ->placeholder(__('admin.no_track_selected'))
                    ->sortable(),

                TextColumn::make('registrations_count')
                    ->label('Members')
                    ->counts('registrations')
                    ->sortable(),

                TextColumn::make('max_members')
                    ->label('Max Members')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞' : $state)
                    ->sortable(),

                TextColumn::make('gender_ratio')
                    ->label('Gender Distribution')
                    ->getStateUsing(function ($record) {
                        $registrations = $record->registrations;
                        if ($registrations->isEmpty()) {
                            return '—';
                        }

                        $categories = app(\App\Settings\EventSettings::class)->gender_categories;
                        $counts = [];

                        foreach ($categories as $category) {
                            $key = $category['key'];
                            $count = $registrations->where('gender', $key)->count();
                            $counts[$key] = $count;
                        }

                        // Show abbreviated format (e.g., "F:2 A:3")
                        $parts = [];
                        foreach ($counts as $key => $count) {
                            $abbr = strtoupper(substr($key, 0, 1));
                            $parts[] = "{$abbr}:{$count}";
                        }

                        return implode(' ', $parts);
                    })
                    ->badge()
                    ->color('info')
                    ->tooltip(function ($record) {
                        $registrations = $record->registrations;
                        if ($registrations->isEmpty()) {
                            return null;
                        }

                        $categories = app(\App\Settings\EventSettings::class)->gender_categories;
                        $locale = app()->getLocale();
                        $total = $registrations->count();

                        if ($total === 0) {
                            return null;
                        }

                        $lines = [];
                        foreach ($categories as $category) {
                            $key = $category['key'];
                            $label = $category['translations'][$locale]['label'] ?? $key;
                            $count = $registrations->where('gender', $key)->count();
                            $percentage = round(($count / $total) * 100, 1);
                            $lines[] = "{$label}: {$count} ({$percentage}%)";
                        }

                        return implode("\n", $lines);
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
