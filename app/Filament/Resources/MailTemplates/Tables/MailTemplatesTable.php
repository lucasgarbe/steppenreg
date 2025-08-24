<?php

namespace App\Filament\Resources\MailTemplates\Tables;

use App\Services\MailTemplateService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('key')
                    ->label('Type')
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
                
                TextColumn::make('subject')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                TextColumn::make('mailLogs_count')
                    ->label('Sent Count')
                    ->counts('mailLogs')
                    ->sortable(),
                
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                
                SelectFilter::make('key')
                    ->label('Type')
                    ->options([
                        'registration_confirmation' => 'Registration Confirmation',
                        'draw_success' => 'Draw Success',
                        'draw_waitlist' => 'Draw Waitlist',
                        'draw_rejection' => 'Draw Rejection',
                    ]),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->form([
                        Textarea::make('preview_subject')
                            ->label('Subject')
                            ->disabled()
                            ->rows(2),
                        
                        Textarea::make('preview_body')
                            ->label('Body')
                            ->disabled()
                            ->rows(10),
                    ])
                    ->fillForm(function ($record) {
                        $service = app(MailTemplateService::class);
                        $preview = $service->previewTemplate($record->key);
                        
                        return [
                            'preview_subject' => $preview['subject'] ?? '',
                            'preview_body' => strip_tags($preview['body'] ?? ''),
                        ];
                    })
                    ->modalWidth('xl'),
                
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
