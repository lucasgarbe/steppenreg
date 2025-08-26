<?php

namespace App\Filament\Resources\Registrations\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starting_number')
                    ->label(__('admin.registrations.columns.start_number'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->whereNotNull('starting_number')->orderBy('starting_number', $direction);
                    })
                    ->searchable(isIndividual: false, isGlobal: false)
                    ->placeholder('—')
                    ->badge()
                    ->formatStateUsing(fn($record) => $record->starting_number_label)
                    ->color(fn($record) => match ($record->starting_number_type) {
                        'main' => 'success',
                        'waitlist' => 'warning',
                        'waitlist_overflow' => 'danger',
                        default => 'gray'
                    }),

                TextColumn::make('name')
                    ->label(__('admin.registrations.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->icon(fn($record) => $record->notes ? 'heroicon-s-document-text' : null)
                    ->iconColor('primary')
                    ->tooltip(fn($record) => $record->notes ? __('admin.registrations.tooltips.has_notes') : null),

                TextColumn::make('email')
                    ->label(__('admin.registrations.columns.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label(__('admin.registrations.columns.age'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender_label')
                    ->label(__('admin.registrations.columns.gender'))
                    ->placeholder(__('admin.form.placeholders.not_specified'))
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'FLINTA*' => 'purple',
                        'All Gender' => 'blue',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('track_name')
                    ->label(__('admin.registrations.columns.track'))
                    ->placeholder(__('admin.form.placeholders.no_track_selected'))
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->whereNotNull('track_id')->orderBy('track_id', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('team.name')
                    ->label(__('admin.registrations.columns.team'))
                    ->placeholder(__('admin.form.placeholders.individual'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('draw_status')
                    ->label(__('admin.registrations.columns.draw_status'))
                    ->badge()
                    ->color(fn($record): string => match ($record->draw_status) {
                        'drawn' => $record->is_withdrawn ? 'danger' : 'success',
                        'waitlist' => 'warning',
                        'not_drawn' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(function($record): string {
                        if ($record->is_withdrawn) {
                            return __('admin.registrations.draw_status.withdrawn');
                        }
                        if ($record->is_waitlist_registered && $record->draw_status === 'waitlist') {
                            $position = $record->getWaitlistPosition();
                            return __('messages.waitlist') . " #{$position}";
                        }
                        return match ($record->draw_status) {
                            'drawn' => __('admin.registrations.draw_status.drawn'),
                            'waitlist' => __('admin.registrations.draw_status.waitlist'),
                            'not_drawn' => __('admin.registrations.draw_status.not_drawn'),
                            default => $record->draw_status,
                        };
                    })
                    ->sortable(),

                TextColumn::make('finish_time')
                    ->label(__('admin.registrations.columns.finish_time'))
                    ->time('H:i')
                    ->placeholder(__('admin.form.placeholders.not_finished'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('admin.registrations.columns.status'))
                    ->badge()
                    ->formatStateUsing(function($record): string {
                        return match ($record->status) {
                            'Finished' => __('admin.registrations.status.finished'),
                            'Starting' => __('admin.registrations.status.starting'),
                            'Paid' => __('admin.registrations.status.paid'),
                            'Drawn' => __('admin.registrations.status.drawn'),
                            'Waitlist' => __('admin.registrations.status.waitlist'),
                            'Registered' => __('admin.registrations.status.registered'),
                            default => $record->status,
                        };
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Finished' => 'success',
                        'Starting' => 'info',
                        'Paid' => 'warning',
                        'Drawn' => 'primary',
                        'Waitlist' => 'warning',
                        'Registered' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('notes')
                    ->label(__('admin.registrations.columns.notes'))
                    ->wrap()
                    ->limit(50)
                    ->placeholder(__('admin.form.placeholders.no_notes'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon(fn($record) => $record->notes ? 'heroicon-s-document-text' : null)
                    ->color(fn($record) => $record->notes ? 'primary' : null),

                TextColumn::make('created_at')
                    ->label(__('admin.registrations.columns.created_at'))
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

                Filter::make('status')
                    ->label('Status')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Select Status')
                            ->options([
                                'Registered' => 'Registered',
                                'Waitlist' => 'Waitlist',
                                'Drawn' => 'Drawn',
                                'Paid' => 'Paid',
                                'Starting' => 'Starting',
                                'Finished' => 'Finished',
                            ])
                            ->placeholder('All statuses')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['status']) || !$data['status']) {
                            return $query;
                        }
                        
                        $status = $data['status'];
                        
                        return match ($status) {
                            'Finished' => $query->whereNotNull('finish_time'),
                            'Starting' => $query->where('starting', true)->whereNull('finish_time'),
                            'Paid' => $query->where('payed', true)->where('starting', false)->whereNull('finish_time'),
                            'Drawn' => $query->where('draw_status', 'drawn')->where('payed', false)->where('starting', false)->whereNull('finish_time'),
                            'Waitlist' => $query->where('draw_status', 'waitlist')->where('payed', false)->where('starting', false)->whereNull('finish_time'),
                            'Registered' => $query->where('draw_status', 'not_drawn')->where('payed', false)->where('starting', false)->whereNull('finish_time'),
                            default => $query,
                        };
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    
                    Action::make('promote_from_waitlist')
                        ->label(__('admin.registrations.actions.promote_from_waitlist'))
                        ->icon('heroicon-o-arrow-up')
                        ->color('success')
                        ->visible(fn($record) => $record->draw_status === 'waitlist' && !$record->is_withdrawn)
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.promote_from_waitlist'))
                        ->modalDescription(fn($record) => __('admin.registrations.confirmations.promote_from_waitlist', ['name' => $record->name]))
                        ->action(function ($record) {
                            DB::transaction(function () use ($record) {
                                // First update the status - the observer will handle starting number assignment
                                $record->update([
                                    'draw_status' => 'drawn',
                                    'promoted_from_waitlist_at' => now()
                                ]);
                                
                                // Generate withdraw token
                                $record->generateWithdrawToken();
                            });
                            
                            $record->refresh(); // Get the updated starting number
                            
                            $message = $record->starting_number ? 
                                __('admin.registrations.notifications.promoted_with_starting_number', [
                                    'name' => $record->name,
                                    'number' => $record->formatted_starting_number
                                ]) :
                                __('admin.registrations.notifications.promoted_from_waitlist', ['name' => $record->name]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('admin.registrations.notifications.promotion_completed'))
                                ->body($message)
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('add_to_waitlist')
                        ->label(__('admin.registrations.actions.add_to_waitlist'))
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->visible(fn($record) => $record->draw_status === 'not_drawn' && !$record->is_withdrawn)
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.add_to_waitlist'))
                        ->modalDescription(fn($record) => __('admin.registrations.confirmations.add_to_waitlist', ['name' => $record->name]))
                        ->action(function ($record) {
                            $record->update([
                                'draw_status' => 'waitlist',
                                'drawn_at' => now()
                            ]);
                            
                            // Generate waitlist token
                            $record->generateWaitlistToken();
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('admin.registrations.notifications.added_to_waitlist'))
                                ->body(__('admin.registrations.notifications.added_to_waitlist_body', ['name' => $record->name]))
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('manual_withdraw')
                        ->label(__('admin.registrations.actions.manual_withdraw'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn($record) => $record->draw_status === 'drawn' && !$record->is_withdrawn)
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.manual_withdraw'))
                        ->modalDescription(fn($record) => __('admin.registrations.confirmations.manual_withdraw', ['name' => $record->name]))
                        ->action(function ($record) {
                            $record->update([
                                'is_withdrawn' => true,
                                'withdrawn_at' => now(),
                                'withdrawal_reason' => 'admin_manual'
                            ]);
                            
                            // Try to promote next waitlist registration
                            $nextWaitlisted = \App\Models\Registration::where('draw_status', 'waitlist')
                                ->where('track_id', $record->track_id)
                                ->where('is_withdrawn', false)
                                ->whereNull('promoted_from_waitlist_at')
                                ->orderBy('drawn_at')
                                ->first();
                                
                            if ($nextWaitlisted) {
                                $nextWaitlisted->update([
                                    'draw_status' => 'drawn',
                                    'promoted_from_waitlist_at' => now()
                                ]);
                                
                                // Generate withdraw token for the newly promoted
                                $nextWaitlisted->generateWithdrawToken();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title(__('admin.registrations.notifications.withdrawal_completed'))
                                    ->body(__('admin.registrations.notifications.withdrew_and_promoted', [
                                        'withdrawn' => $record->name,
                                        'promoted' => $nextWaitlisted->name
                                    ]))
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('admin.registrations.notifications.withdrawal_completed'))
                                    ->body(__('admin.registrations.notifications.withdrew_no_promotion', ['name' => $record->name]))
                                    ->success()
                                    ->send();
                            }
                        }),
                    
                    Action::make('send_withdrawal_link')
                        ->label(__('admin.registrations.actions.send_withdrawal_link'))
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->visible(fn($record) => $record->draw_status === 'drawn' && !$record->is_withdrawn)
                        ->action(function ($record) {
                            // Generate token if not exists
                            if (!$record->withdraw_token) {
                                $record->generateWithdrawToken();
                            }
                            
                            // Send notification email
                            \App\Jobs\Mail\SendDrawNotification::dispatch($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('admin.registrations.notifications.withdrawal_link_sent'))
                                ->body(__('admin.registrations.notifications.withdrawal_link_sent_body', ['email' => $record->email]))
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('send_draw_results')
                        ->label(__('admin.registrations.actions.send_draw_results'))
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->visible(fn($record) => $record->draw_status !== 'not_drawn' && !$record->is_withdrawn)
                        ->action(function ($record) {
                            // Generate tokens if they don't exist
                            if ($record->draw_status === 'drawn' && !$record->withdraw_token) {
                                $record->generateWithdrawToken();
                            }
                            if (($record->draw_status === 'waitlist' || $record->can_join_waitlist) && !$record->waitlist_token) {
                                $record->generateWaitlistToken();
                            }
                            
                            // Send draw notification email
                            \App\Jobs\Mail\SendDrawNotification::dispatch($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('admin.registrations.notifications.draw_results_sent'))
                                ->body(__('admin.registrations.notifications.draw_results_sent_body', ['email' => $record->email]))
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                     BulkAction::make('mark_as_paid')
                        ->label(__('admin.registrations.actions.mark_as_paid'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update(['payed' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_starting')
                        ->label(__('admin.registrations.actions.mark_as_starting'))
                        ->icon('heroicon-o-play-circle')
                        ->color('info')
                        ->action(fn(Collection $records) => $records->each->update(['starting' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_drawn')
                        ->label(__('admin.registrations.actions.mark_as_drawn'))
                        ->icon('heroicon-o-star')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update([
                            'draw_status' => 'drawn',
                            'drawn_at' => now()
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_waitlist')
                        ->label(__('admin.registrations.actions.mark_as_waitlist'))
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(fn(Collection $records) => $records->each->update([
                            'draw_status' => 'waitlist',
                            'drawn_at' => now()
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_not_drawn')
                        ->label(__('admin.registrations.actions.mark_as_not_drawn'))
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(fn(Collection $records) => $records->each->update([
                            'draw_status' => 'not_drawn',
                            'drawn_at' => null
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('assign_starting_numbers')
                        ->label(__('admin.registrations.actions.assign_starting_numbers'))
                        ->icon('heroicon-o-hashtag')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $service = app(\App\Services\StartingNumberService::class);
                            $results = $service->bulkAssignNumbers($records->pluck('id')->toArray());
                            
                            $assigned = count($results['assigned'] ?? []);
                            $failed = count($results['failed'] ?? []);
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Starting numbers assigned")
                                ->body("Assigned: {$assigned}, Failed: {$failed}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('generate_waitlist_tokens')
                        ->label('Generate Waitlist Links')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $generated = 0;
                            foreach ($records as $record) {
                                if ($record->can_join_waitlist) {
                                    $record->generateWaitlistToken();
                                    $generated++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Waitlist tokens generated")
                                ->body("Generated {$generated} waitlist links for eligible registrations")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('generate_withdraw_tokens')
                        ->label('Generate Withdraw Links')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $generated = 0;
                            foreach ($records as $record) {
                                if ($record->can_withdraw) {
                                    $record->generateWithdrawToken();
                                    $generated++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Withdrawal tokens generated")
                                ->body("Generated {$generated} withdrawal links for drawn registrations")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('send_draw_notifications')
                        ->label('Send Draw Result Emails')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $sent = 0;
                            foreach ($records as $record) {
                                // Only send to records that have a draw result (not 'not_drawn')
                                if ($record->draw_status !== 'not_drawn') {
                                    // Generate tokens first if needed
                                    if ($record->draw_status === 'drawn' && !$record->withdraw_token) {
                                        $record->generateWithdrawToken();
                                    }
                                    if (($record->draw_status === 'not_drawn' || $record->can_join_waitlist) && !$record->waitlist_token) {
                                        $record->generateWaitlistToken();
                                    }
                                    
                                    \App\Jobs\Mail\SendDrawNotification::dispatch($record);
                                    $sent++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Draw notification emails queued")
                                ->body("Sent {$sent} draw result emails to queue for processing")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Draw Result Emails')
                        ->modalDescription('This will send draw result emails to all selected participants. Make sure tokens are generated first!')
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
