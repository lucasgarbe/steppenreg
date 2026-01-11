<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Filament\Exports\RegistrationExporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('starting_number')
                    ->label(__('admin.registrations.columns.start_number'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->whereNotNull('starting_number')->orderBy('starting_number', $direction);
                    })
                    ->placeholder('---')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        $service = app(\App\Domain\StartingNumber\Services\StartingNumberService::class);

                        return $service->getNumberLabel($record);
                    })
                    ->color(function ($record) {
                        $service = app(\App\Domain\StartingNumber\Services\StartingNumberService::class);

                        return match ($service->getNumberType($record)) {
                            'main' => 'success',
                            default => 'gray',
                        };
                    })
                    ->visible(fn () => config('steppenreg.features.starting_numbers', true)),

                TextColumn::make('name')
                    ->label(__('admin.registrations.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->icon(fn ($record) => $record?->notes ? 'heroicon-s-document-text' : null)
                    ->iconColor('primary')
                    ->tooltip(fn ($record) => $record?->notes ? __('admin.registrations.tooltips.has_notes') : null),

                TextColumn::make('email')
                    ->label(__('admin.registrations.columns.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label(__('admin.registrations.columns.age'))
                    ->sortable()
                    ->numeric()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state < 18 => 'danger',
                        $state >= 18 && $state <= 25 => 'warning',
                        $state >= 26 && $state <= 50 => 'success',
                        $state > 50 => 'primary',
                        default => 'gray'
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('gender_label')
                    ->label(__('admin.registrations.columns.gender'))
                    ->placeholder(__('admin.form.placeholders.not_specified'))
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'FLINTA*' => 'purple',
                        'All Gender' => 'blue',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('track_name')
                    ->label(__('admin.registrations.columns.track'))
                    ->placeholder(__('admin.form.placeholders.no_track_selected'))
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
                    ->color(fn ($record): string => match ($record?->draw_status) {
                        'drawn' => 'success',
                        'not_drawn' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($record): string {
                        return match ($record?->draw_status) {
                            'drawn' => __('admin.registrations.draw_status.drawn'),
                            'not_drawn' => __('admin.registrations.draw_status.not_drawn'),
                            default => $record?->draw_status ?? '',
                        };
                    })
                    ->sortable(),

                ToggleColumn::make('payed')
                    ->label(__('Bezahlt'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('finish_time')
                    ->label(__('admin.registrations.columns.finish_time'))
                    ->time('H:i')
                    ->placeholder(__('admin.form.placeholders.not_finished'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('admin.registrations.columns.status'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->formatStateUsing(function ($record): string {
                        return match ($record?->status) {
                            'Finished' => __('admin.registrations.status.finished'),
                            'Starting' => __('admin.registrations.status.starting'),
                            'Paid' => __('admin.registrations.status.paid'),
                            'Drawn' => __('admin.registrations.status.drawn'),
                            'Registered' => __('admin.registrations.status.registered'),
                            default => $record?->status ?? '',
                        };
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Finished' => 'success',
                        'Starting' => 'info',
                        'Paid' => 'warning',
                        'Drawn' => 'primary',
                        'Registered' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('notes')
                    ->label(__('admin.registrations.columns.notes'))
                    ->wrap()
                    ->limit(50)
                    ->placeholder(__('admin.form.placeholders.no_notes'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon(fn ($record) => $record?->notes ? 'heroicon-s-document-text' : null)
                    ->color(fn ($record) => $record?->notes ? 'primary' : null),

                TextColumn::make('created_at')
                    ->label(__('admin.registrations.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ...self::getCustomQuestionColumns(),
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
                                        $label .= ' ('.$track['distance'].' km)';
                                    }
                                    $options[$track['id']] = $label;
                                }

                                return $options;
                            })
                            ->placeholder('All tracks'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['track_id'], fn ($query, $trackId) => $query->where('track_id', $trackId));
                    }),

                SelectFilter::make('payed')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'paid' => $query->whereRaw('payed = true'),
                            'unpaid' => $query->whereRaw('payed = false'),
                            default => $query,
                        };
                    }),

                SelectFilter::make('starting')
                    ->label('Starting Status')
                    ->options([
                        'starting' => 'Starting',
                        'not_starting' => 'Not Starting',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'starting' => $query->whereRaw('starting = true'),
                            'not_starting' => $query->whereRaw('starting = false'),
                            default => $query,
                        };
                    }),
                /**/
                SelectFilter::make('finish_time')
                    ->label('Completion Status')
                    ->options([
                        'finished' => 'Finished',
                        'not_finished' => 'Not Finished',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'finished' => $query->whereNotNull('finish_time'),
                            'not_finished' => $query->whereNull('finish_time'),
                            default => $query,
                        };
                    }),
                /**/
                SelectFilter::make('registration_type')
                    ->label('Registration Type')
                    ->options([
                        'team' => 'Teams',
                        'individual' => 'Individuals',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'team' => $query->whereNotNull('team_id'),
                            'individual' => $query->whereNull('team_id'),
                            default => $query,
                        };
                    }),
                /**/
                SelectFilter::make('draw_status')
                    ->label('Draw Status')
                    ->options([
                        'drawn' => 'Drawn',
                        'not_drawn' => 'Not Drawn',
                    ])
                    ->placeholder('All statuses'),
                /**/
                Filter::make('gender')
                    ->label('Gender')
                    ->form([
                        \Filament\Forms\Components\Select::make('gender')
                            ->label('Select Gender')
                            ->options([
                                'flinta' => 'FLINTA*',
                                'all_gender' => 'All Gender',
                            ])
                            ->placeholder('All genders'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['gender'] ?? false,
                            fn (Builder $query, $gender) => $query->where('gender', $gender)
                        );
                    }),
                /**/
                Filter::make('age_group')
                    ->label('Age Group')
                    ->form([
                        \Filament\Forms\Components\Select::make('age_group')
                            ->label('Select Age Group')
                            ->options([
                                'under_18' => 'Under 18',
                                '18_25' => '18-25',
                                '26_35' => '26-35',
                                '36_50' => '36-50',
                                '51_65' => '51-65',
                                'over_65' => 'Over 65',
                            ])
                            ->placeholder('All ages'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['age_group']) || ! $data['age_group']) {
                            return $query;
                        }

                        return match ($data['age_group']) {
                            'under_18' => $query->where('age', '<', 18),
                            '18_25' => $query->whereBetween('age', [18, 25]),
                            '26_35' => $query->whereBetween('age', [26, 35]),
                            '36_50' => $query->whereBetween('age', [36, 50]),
                            '51_65' => $query->whereBetween('age', [51, 65]),
                            'over_65' => $query->where('age', '>', 65),
                            default => $query,
                        };
                    }),

                ...self::getCustomQuestionFilters(),

                TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('arrived')
                    ->label('Angekommen')
                    ->color('success')
                    ->button()
                    ->action(fn ($record) => $record->update(['finish_time' => now()])),

                Action::make('DNF')
                    ->label('DNF')
                    ->color('danger')
                    ->button()
                    ->action(fn ($record) => $record->update(['finish_time' => '00:00'])),

                ActionGroup::make([
                    EditAction::make(),

                    Action::make('mark_as_drawn')
                        ->label(__('admin.registrations.actions.mark_as_drawn'))
                        ->icon('heroicon-o-star')
                        ->color('success')
                        ->visible(fn ($record) => $record?->draw_status !== 'drawn')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.mark_as_drawn'))
                        ->modalDescription(fn ($record) => "Mark {$record->name} as drawn?")
                        ->action(function ($record) {
                            $record->update([
                                'draw_status' => 'drawn',
                                'drawn_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Draw status updated')
                                ->body("{$record->name} marked as drawn")
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_as_not_drawn')
                        ->label(__('admin.registrations.actions.mark_as_not_drawn'))
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->visible(fn ($record) => $record?->draw_status !== 'not_drawn')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.mark_as_not_drawn'))
                        ->modalDescription(fn ($record) => "Mark {$record->name} as not drawn?")
                        ->action(function ($record) {
                            $record->update([
                                'draw_status' => 'not_drawn',
                                'drawn_at' => null,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Draw status updated')
                                ->body("{$record->name} marked as not drawn")
                                ->success()
                                ->send();
                        }),

                    Action::make('send_registration_confirmation')
                        ->label('Send Registration Confirmation')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Send Registration Confirmation')
                        ->modalDescription(fn ($record) => "Send registration confirmation email to {$record->name} ({$record->email})?")
                        ->action(function ($record) {
                            \App\Jobs\Mail\SendRegistrationConfirmation::dispatch($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Registration confirmation sent')
                                ->body("Confirmation email queued for {$record->email}")
                                ->success()
                                ->send();
                        }),

                    Action::make('send_draw_results')
                        ->label(__('admin.registrations.actions.send_draw_results'))
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->visible(fn ($record) => in_array($record?->draw_status, ['drawn', 'not_drawn']))
                        ->action(function ($record) {
                            // Send draw notification email
                            \App\Jobs\Mail\SendDrawNotification::dispatch($record);

                            \Filament\Notifications\Notification::make()
                                ->title(__('admin.registrations.notifications.draw_results_sent'))
                                ->body(__('admin.registrations.notifications.draw_results_sent_body', ['email' => $record->email]))
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_as_paid')
                        ->label(__('admin.registrations.actions.mark_as_paid'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => ! $record?->payed)
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.mark_as_paid'))
                        ->modalDescription(fn ($record) => "Mark {$record->name} as paid?")
                        ->action(function ($record) {
                            $record->update(['payed' => true]);

                            \Filament\Notifications\Notification::make()
                                ->title('Payment status updated')
                                ->body("{$record->name} marked as paid")
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_as_starting')
                        ->label(__('admin.registrations.actions.mark_as_starting'))
                        ->icon('heroicon-o-play-circle')
                        ->color('info')
                        ->visible(fn ($record) => ! $record?->starting && $record?->payed)
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.registrations.actions.mark_as_starting'))
                        ->modalDescription(fn ($record) => "Mark {$record->name} as starting?")
                        ->action(function ($record) {
                            $record->update(['starting' => true]);

                            \Filament\Notifications\Notification::make()
                                ->title('Starting status updated')
                                ->body("{$record->name} marked as starting")
                                ->success()
                                ->send();
                        }),

                    Action::make('assign_starting_number')
                        ->label('Assign Starting Number')
                        ->icon('heroicon-o-hashtag')
                        ->color('warning')
                        ->visible(fn ($record) => config('steppenreg.features.starting_numbers', true) &&
                            $record?->draw_status === 'drawn' &&
                            ! $record?->starting_number
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Assign Starting Number')
                        ->modalDescription(fn ($record) => "Assign starting number to {$record->name}?")
                        ->action(function ($record) {
                            $service = app(\App\Domain\StartingNumber\Services\StartingNumberService::class);
                            $number = $service->assignNumber($record);

                            if ($number) {
                                $record->update(['starting_number' => $number]);
                                \Filament\Notifications\Notification::make()
                                    ->title('Starting number assigned')
                                    ->body('Assigned starting number '.$service->formatNumber($number)." to {$record->name}")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Assignment failed')
                                    ->body("Could not assign starting number to {$record->name}")
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_as_paid')
                        ->label(__('admin.registrations.actions.mark_as_paid'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['payed' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_starting')
                        ->label(__('admin.registrations.actions.mark_as_starting'))
                        ->icon('heroicon-o-play-circle')
                        ->color('info')
                        ->action(fn (Collection $records) => $records->each->update(['starting' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_drawn')
                        ->label(__('admin.registrations.actions.mark_as_drawn'))
                        ->icon('heroicon-o-star')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update([
                            'draw_status' => 'drawn',
                            'drawn_at' => now(),
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_not_drawn')
                        ->label(__('admin.registrations.actions.mark_as_not_drawn'))
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(fn (Collection $records) => $records->each->update([
                            'draw_status' => 'not_drawn',
                            'drawn_at' => null,
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('assign_starting_numbers')
                        ->label(__('admin.registrations.actions.assign_starting_numbers'))
                        ->icon('heroicon-o-hashtag')
                        ->color('info')
                        ->visible(fn () => config('steppenreg.features.starting_numbers', true))
                        ->action(function (Collection $records) {
                            $service = app(\App\Domain\StartingNumber\Services\StartingNumberService::class);
                            $results = $service->bulkAssignNumbers($records->pluck('id')->toArray());

                            $assigned = count($results['assigned'] ?? []);
                            $failed = count($results['failed'] ?? []);

                            \Filament\Notifications\Notification::make()
                                ->title('Starting numbers assigned')
                                ->body("Assigned: {$assigned}, Failed: {$failed}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('send_registration_confirmations')
                        ->label('Send Registration Confirmations')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $sent = 0;
                            foreach ($records as $record) {
                                \App\Jobs\Mail\SendRegistrationConfirmation::dispatch($record);
                                $sent++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Registration confirmations queued')
                                ->body("Sent {$sent} registration confirmation emails to queue for processing")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Registration Confirmations')
                        ->modalDescription('This will send registration confirmation emails to all selected participants.')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('send_draw_notifications')
                        ->label('Send Draw Result Emails')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $sent = 0;
                            foreach ($records as $record) {
                                // Only send to registrations that have been processed in the draw
                                if (in_array($record->draw_status, ['drawn', 'not_drawn'])) {
                                    \App\Jobs\Mail\SendDrawNotification::dispatch($record);
                                    $sent++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Draw notification emails queued')
                                ->body("Sent {$sent} draw result emails to queue for processing")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Draw Result Emails')
                        ->modalDescription('This will send draw result emails to all selected participants (drawn and not drawn).')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('send_custom_email')
                        ->label('Send Custom Email')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->fillForm(fn (Collection $records) => [
                            'to' => $records->map(fn ($record) => $record->email)->implode(', '),
                        ])
                        ->form([
                            \Filament\Forms\Components\TextInput::make('to')
                                ->readOnly(),
                            \Filament\Forms\Components\TextInput::make('subject')
                                ->label('Email Subject')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter email subject'),
                            \Filament\Forms\Components\RichEditor::make('message')
                                ->label('Email Message')
                                ->required()
                                ->placeholder('Enter your message here...')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'link',
                                    'orderedList',
                                    'bulletList',
                                    'h2',
                                    'h3',
                                    'blockquote',
                                    'codeBlock',
                                    'undo',
                                    'redo',
                                ])
                                ->columnSpan('full')
                                ->helperText('You can use template variables like {{name}}, {{email}}, {{track_name}} to personalize emails for each recipient.'),
                            \Filament\Forms\Components\Placeholder::make('variables_help')
                                ->label('Available Template Variables')
                                ->content(function () {
                                    $variables = \App\Models\MailTemplate::getAvailableVariables();
                                    $help = "Use these variables in your subject and message to personalize emails:\n\n";

                                    foreach ($variables as $key => $description) {
                                        $help .= '• **{{'.$key.'}}** - '.$description."\n";
                                    }

                                    $help .= "\nExample: \"Dear {{name}}, you are registered for {{track_name}}!\"";

                                    return $help;
                                })
                                ->columnSpan('full'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $sent = 0;
                            foreach ($records as $record) {
                                \App\Jobs\Mail\SendFlexibleMail::dispatch(
                                    $record,
                                    $data['subject'],
                                    $data['message']
                                );
                                $sent++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Custom emails queued')
                                ->body("Sent {$sent} custom emails to queue for processing")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Custom Email')
                        ->modalDescription(function (Collection $records) {
                            $count = $records->count();

                            return "This will send a custom email to {$count} selected participant(s).";
                        })
                        ->modalWidth('xl')
                        ->deselectRecordsAfterCompletion(),

                    ExportBulkAction::make()
                        ->exporter(RegistrationExporter::class)
                        ->label(__('messages.export')),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultSort('created_at', 'desc');
    }

    protected static function getCustomQuestionColumns(): array
    {
        $columns = [];
        $eventSettings = app(\App\Settings\EventSettings::class);
        $customQuestions = $eventSettings->custom_questions ?? [];

        foreach ($customQuestions as $question) {
            $key = $question['key'];
            $label = $question['translations']['en']['label'] ?? $key;

            $columns[] = TextColumn::make("custom_answers.{$key}")
                ->label($label)
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false)
                ->formatStateUsing(function ($state) use ($question) {
                    if ($question['type'] === 'checkbox' && is_array($state)) {
                        return implode(', ', $state);
                    }
                    if (empty($state)) {
                        return '---';
                    }
                    // For select/radio, try to show label instead of value
                    if (in_array($question['type'], ['select', 'radio'])) {
                        $option = collect($question['options'] ?? [])
                            ->firstWhere('value', $state);
                        if ($option) {
                            return $option['label_en'] ?? $state;
                        }
                    }

                    return $state;
                })
                ->placeholder('---');
        }

        return $columns;
    }

    protected static function getCustomQuestionFilters(): array
    {
        $filters = [];
        $eventSettings = app(\App\Settings\EventSettings::class);
        $customQuestions = $eventSettings->custom_questions ?? [];

        foreach ($customQuestions as $question) {
            $key = $question['key'];
            $label = $question['translations']['en']['label'] ?? $key;

            // Only add filters for select and radio types
            if (in_array($question['type'], ['select', 'radio'])) {
                $filters[] = SelectFilter::make("custom_answers.{$key}")
                    ->label($label)
                    ->options(collect($question['options'] ?? [])
                        ->pluck('label_en', 'value')
                        ->toArray())
                    ->query(function (Builder $query, array $data) use ($key) {
                        if (! empty($data['value'])) {
                            $query->where("custom_answers->{$key}", $data['value']);
                        }
                    });
            }
        }

        return $filters;
    }
}
