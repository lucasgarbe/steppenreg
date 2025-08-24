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
                    ->label('Start #')
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
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label('Age')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender_label')
                    ->label('Gender')
                    ->placeholder('Not specified')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'FLINTA*' => 'purple',
                        'All Gender' => 'blue',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('track_name')
                    ->label('Track')
                    ->placeholder('No track selected')
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->whereNotNull('track_id')->orderBy('track_id', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('team.name')
                    ->label('Team')
                    ->placeholder('Individual')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('draw_status')
                    ->label('Draw Status')
                    ->badge()
                    ->color(fn($record): string => match ($record->draw_status) {
                        'drawn' => $record->is_withdrawn ? 'danger' : 'success',
                        'waitlist' => 'warning',
                        'not_drawn' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(function($record): string {
                        if ($record->is_withdrawn) {
                            return 'Withdrawn';
                        }
                        if ($record->is_waitlist_registered && $record->draw_status === 'waitlist') {
                            $position = $record->getWaitlistPosition();
                            return "Waitlist #{$position}";
                        }
                        return match ($record->draw_status) {
                            'drawn' => 'Drawn',
                            'waitlist' => 'Waitlist',
                            'not_drawn' => 'Not Drawn',
                            default => $record->draw_status,
                        };
                    })
                    ->sortable(),

                TextColumn::make('finish_time')
                    ->label('Finish Time')
                    ->time('H:i')
                    ->placeholder('Not finished')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        ->label('Promote to Drawn')
                        ->icon('heroicon-o-arrow-up')
                        ->color('success')
                        ->visible(fn($record) => $record->draw_status === 'waitlist' && !$record->is_withdrawn)
                        ->requiresConfirmation()
                        ->modalHeading('Promote from Waitlist')
                        ->modalDescription(fn($record) => "Are you sure you want to promote {$record->name} from waitlist to drawn status?")
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
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Promotion Completed')
                                ->body("Promoted {$record->name} from waitlist to drawn status" . ($record->starting_number ? " (Starting #{$record->formatted_starting_number})" : ""))
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('add_to_waitlist')
                        ->label('Add to Waitlist')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->visible(fn($record) => $record->draw_status === 'not_drawn' && !$record->is_withdrawn)
                        ->requiresConfirmation()
                        ->modalHeading('Add to Waitlist')
                        ->modalDescription(fn($record) => "Are you sure you want to add {$record->name} to the waitlist?")
                        ->action(function ($record) {
                            $record->update([
                                'draw_status' => 'waitlist',
                                'drawn_at' => now()
                            ]);
                            
                            // Generate waitlist token
                            $record->generateWaitlistToken();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Added to Waitlist')
                                ->body("Added {$record->name} to the waitlist")
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('manual_withdraw')
                        ->label('Withdraw')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn($record) => $record->draw_status === 'drawn' && !$record->is_withdrawn)
                        ->requiresConfirmation()
                        ->modalHeading('Manual Withdrawal')
                        ->modalDescription(fn($record) => "Are you sure you want to manually withdraw {$record->name} from the event?")
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
                                    ->title('Withdrawal Completed')
                                    ->body("Withdrew {$record->name} and promoted {$nextWaitlisted->name} from waitlist")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Withdrawal Completed')
                                    ->body("Withdrew {$record->name} (no waitlisted participants to promote)")
                                    ->success()
                                    ->send();
                            }
                        }),
                    
                    Action::make('send_withdrawal_link')
                        ->label('Send Withdrawal Link')
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
                                ->title('Withdrawal Link Sent')
                                ->body("Sent withdrawal link to {$record->email}")
                                ->success()
                                ->send();
                        }),
                ])
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

                    BulkAction::make('assign_starting_numbers')
                        ->label('Assign Starting Numbers')
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
