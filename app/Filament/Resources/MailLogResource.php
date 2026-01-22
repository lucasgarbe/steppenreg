<?php

namespace App\Filament\Resources;

use App\Models\MailLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MailLogResource extends Resource
{
    protected static ?string $model = MailLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Mail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Logs';

    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recipient_email')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('template_key')
                    ->label('Template')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'registration_confirmation' => 'Registration',
                        'draw_success' => 'Draw Success',
                        'draw_waitlist' => 'Waitlist',
                        'draw_rejection' => 'Rejection',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'registration_confirmation' => 'success',
                        'draw_success' => 'success',
                        'draw_waitlist' => 'warning',
                        'draw_rejection' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MailLog $record): string => match (true) {
                        $record->status === 'sent' => 'success',
                        $record->status === 'failed' => 'danger',
                        $record->status === 'queued' && $record->isRateLimited() => 'warning',
                        $record->status === 'queued' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (MailLog $record): string => match (true) {
                        $record->status === 'queued' && $record->isRateLimited() => 'Rate Limited',
                        default => ucfirst($record->status),
                    }),

                TextColumn::make('registration.name')
                    ->label('Participant')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Queued')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not sent'),

                TextColumn::make('attempt_count')
                    ->label('Attempts')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state === 1 => 'success',
                        $state <= 5 => 'info',
                        $state <= 10 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Not started' : (string) $state),

                TextColumn::make('rate_limit_count')
                    ->label('Rate Limited')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->default(0)
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? '-' : $state.'x')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_rate_limited_at')
                    ->label('Last Rate Limited')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return $state && strlen($state) > 30 ? $state : null;
                    })
                    ->placeholder('No errors'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('template_key')
                    ->label('Template')
                    ->options([
                        'registration_confirmation' => 'Registration Confirmation',
                        'draw_success' => 'Draw Success',
                        'draw_waitlist' => 'Draw Waitlist',
                        'draw_rejection' => 'Draw Rejection',
                    ]),

                Filter::make('recent')
                    ->label('Recent (Last 24h)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDay()))
                    ->default(),

                Filter::make('rate_limited')
                    ->label('Recently Rate Limited')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('rate_limit_count', '>', 0)
                        ->where('last_rate_limited_at', '>=', now()->subHour())),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\MailLogResource\Pages\ListMailLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
