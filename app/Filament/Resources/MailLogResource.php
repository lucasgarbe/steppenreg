<?php

namespace App\Filament\Resources;

use App\Models\MailLog;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class MailLogResource extends Resource
{
    protected static ?string $model = MailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    
    protected static ?string $navigationLabel = 'Mail Logs';
    
    protected static ?int $navigationSort = 25;

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
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'queued' => 'warning',
                        default => 'gray',
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
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'failed')
                    ->action(function ($record) {
                        $record->update(['status' => 'queued', 'error_message' => null]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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