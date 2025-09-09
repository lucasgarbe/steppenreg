<?php

namespace App\Filament\Pages;

use App\Models\WaitlistEntry;
use App\Models\Registration;
use App\Settings\EventSettings;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ManageWaitlist extends Page implements HasTable
{
    use InteractsWithTable;

    public array $previewedEntries = [];
    public string $selectedTrackId = 'all';
    public int $targetParticipants = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Manage Waitlist';

    protected static ?int $navigationSort = 35;

    protected static ?string $title = 'Waitlist Pool Management';

    protected string $view = 'filament.pages.manage-waitlist';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WaitlistEntry::query()
                    ->with(['registration.team'])
                    ->whereHas('registration', function ($query) {
                        $query->where('draw_status', 'waitlist');
                    })
            )
            ->columns([
                TextColumn::make('entry_type')
                    ->label('Type')
                    ->getStateUsing(fn($record) => $record->isTeamEntry() ? 'Team' : 'Individual')
                    ->badge()
                    ->color(fn($state) => $state === 'Team' ? 'info' : 'gray'),

                TextColumn::make('registration.name')
                    ->label('Name / Team Name')
                    ->getStateUsing(function ($record) {
                        if ($record->isTeamEntry()) {
                            return $record->registration->team->name . ' - ' . $record->registration->name;
                        }
                        return $record->registration->name;
                    })
                    ->searchable(['registration.name', 'registration.team.name']),

                TextColumn::make('participant_count')
                    ->label('Participants')
                    ->getStateUsing(function ($record) {
                        if ($record->isTeamEntry()) {
                            return $record->getTeamMembers()->count();
                        }
                        return 1;
                    })
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state === 1 => 'gray',
                        $state <= 3 => 'warning',
                        default => 'success'
                    }),

                TextColumn::make('registration.track_name')
                    ->label('Track')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('gender_composition')
                    ->label('Gender')
                    ->getStateUsing(function ($record) {
                        if ($record->isTeamEntry()) {
                            $members = $record->getTeamMembers();
                            $flinta = $members->where('gender', 'flinta')->count();
                            $allGender = $members->where('gender', 'all_gender')->count();
                            return "{$flinta}F / {$allGender}A";
                        }
                        return $record->registration->gender === 'flinta' ? 'FLINTA*' : 'All Gender';
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        if (!$record->isTeamEntry()) {
                            return $record->registration->gender === 'flinta' ? 'purple' : 'blue';
                        }

                        // For teams, color based on FLINTA* percentage
                        preg_match('/(\d+)F/', $state, $flintaMatch);
                        preg_match('/(\d+)A/', $state, $allGenderMatch);

                        $flinta = (int)($flintaMatch[1] ?? 0);
                        $total = $flinta + (int)($allGenderMatch[1] ?? 0);

                        if ($total === 0) return 'gray';

                        $flintaPercentage = ($flinta / $total) * 100;

                        return match (true) {
                            $flintaPercentage >= 50 => 'purple',
                            $flintaPercentage >= 30 => 'info',
                            $flintaPercentage > 0 => 'warning',
                            default => 'gray'
                        };
                    }),

                TextColumn::make('registered_at')
                    ->label('Joined Waitlist')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('registration.email')
                    ->label('Contact Email')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('track')
                    ->label('Track')
                    ->options(function () {
                        $tracks = app(EventSettings::class)->tracks ?? [];
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
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? false,
                            fn(Builder $query, $trackId) => $query->whereHas('registration', function ($q) use ($trackId) {
                                $q->where('track_id', $trackId);
                            })
                        );
                    }),

                SelectFilter::make('type')
                    ->label('Entry Type')
                    ->options([
                        'individual' => 'Individual',
                        'team' => 'Team',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? false,
                            function (Builder $query, $type) {
                                if ($type === 'team') {
                                    return $query->where('is_team_captain', true);
                                } else {
                                    return $query->where(function ($q) {
                                        $q->where('is_team_captain', false)->orWhereNull('is_team_captain');
                                    });
                                }
                            }
                        );
                    }),
            ])
            ->actions([
                Action::make('promote')
                    ->label('Promote to Drawn')
                    ->icon('heroicon-o-arrow-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => 'Promote ' . ($record->isTeamEntry() ? 'Team' : 'Individual') . ' from Waitlist')
                    ->modalDescription(function ($record) {
                        if ($record->isTeamEntry()) {
                            $memberCount = $record->getTeamMembers()->count();
                            return "Promote team '{$record->registration->team->name}' with {$memberCount} members to drawn status?";
                        }
                        return "Promote '{$record->registration->name}' to drawn status?";
                    })
                    ->action(function ($record) {
                        $this->promoteWaitlistEntry($record);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('promote_selected')
                        ->label('Promote Selected to Drawn')
                        ->icon('heroicon-o-arrow-up')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Promote Selected from Waitlist')
                        ->modalDescription('Promote all selected entries to drawn status?')
                        ->action(function (Collection $records) {
                            $promoted = 0;
                            $totalParticipants = 0;

                            foreach ($records as $record) {
                                $participantCount = $record->isTeamEntry() ?
                                    $record->getTeamMembers()->count() : 1;

                                $this->promoteWaitlistEntry($record, false);
                                $promoted++;
                                $totalParticipants += $participantCount;
                            }

                            Notification::make()
                                ->title('Waitlist Promotions Completed')
                                ->body("Promoted {$promoted} entries ({$totalParticipants} total participants) from waitlist to drawn status")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                ]),
            ])
            ->defaultSort('registered_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('random_draw')
                ->label('Random Draw from Pool')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('track_id')
                        ->label('Track Selection')
                        ->options(function () {
                            $tracks = app(\App\Settings\EventSettings::class)->tracks ?? [];
                            $options = ['all' => 'All Tracks'];

                            foreach ($tracks as $track) {
                                $label = $track['name'];
                                if (isset($track['distance'])) {
                                    $label .= ' (' . $track['distance'] . ' km)';
                                }
                                $options[$track['id']] = $label;
                            }

                            return $options;
                        })
                        ->default('all')
                        ->required()
                        ->helperText('Select the track to draw from, or "All Tracks" for pool-wide draw'),

                    TextInput::make('target_participants')
                        ->label('Target Participants')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(10)
                        ->helperText('Approximate number of participants to select. Teams count as multiple participants and may cause the total to exceed this number.')
                ])
                ->modalHeading('Random Draw from Waitlist Pool')
                ->modalSubmitActionLabel('Preview Draw')
                ->action(function (array $data) {
                    $this->selectedTrackId = $data['track_id'];
                    $this->targetParticipants = (int)$data['target_participants'];
                    $this->previewRandomDraw($this->targetParticipants, $this->selectedTrackId);
                }),

            Action::make('confirm_draw')
                ->label('Confirm & Execute Draw')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn() => !empty($this->previewedEntries))
                ->requiresConfirmation()
                ->modalHeading('Confirm Random Draw')
                ->modalDescription(fn() => $this->getConfirmationDescription())
                ->action(function () {
                    $this->executeConfirmedDraw();
                }),

            Action::make('cancel_preview')
                ->label('Cancel Preview')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn() => !empty($this->previewedEntries))
                ->action(function () {
                    $this->previewedEntries = [];
                    $this->selectedTrackId = 'all';
                    $this->targetParticipants = 0;
                    Notification::make()
                        ->title('Draw Preview Cancelled')
                        ->body('You can start a new random draw.')
                        ->info()
                        ->send();
                }),
        ];
    }

    private function promoteWaitlistEntry(WaitlistEntry $entry, bool $sendNotification = true): void
    {
        if ($entry->isTeamEntry()) {
            // Promote entire team
            $teamMembers = $entry->getTeamMembers();
            foreach ($teamMembers as $member) {
                $member->update([
                    'draw_status' => 'drawn',
                    'drawn_at' => now(),
                ]);

                // Generate withdraw token
                $member->generateWithdrawToken();
                
                // Send draw success email for each team member
                \App\Jobs\Mail\SendDrawNotification::dispatch($member);
            }

            $participantCount = $teamMembers->count();
            $teamName = $entry->registration->team->name;

            if ($sendNotification) {
                Notification::make()
                    ->title('Team Promoted')
                    ->body("Team '{$teamName}' with {$participantCount} members promoted from waitlist")
                    ->success()
                    ->send();
            }

            Log::info('Team promoted from waitlist', [
                'team_name' => $teamName,
                'member_count' => $participantCount,
                'captain_id' => $entry->registration->id
            ]);
        } else {
            // Promote individual
            $entry->registration->update([
                'draw_status' => 'drawn',
                'drawn_at' => now(),
            ]);

            // Generate withdraw token
            $entry->registration->generateWithdrawToken();
            
            // Send draw success email for individual
            \App\Jobs\Mail\SendDrawNotification::dispatch($entry->registration);

            if ($sendNotification) {
                Notification::make()
                    ->title('Individual Promoted')
                    ->body("'{$entry->registration->name}' promoted from waitlist")
                    ->success()
                    ->send();
            }

            Log::info('Individual promoted from waitlist', [
                'registration_id' => $entry->registration->id,
                'name' => $entry->registration->name
            ]);
        }

        // Delete waitlist entry
        $entry->delete();
    }

    private function performRandomDraw(int $count): void
    {
        $waitlistEntries = WaitlistEntry::with(['registration.team'])
            ->whereHas('registration', function ($query) {
                $query->where('draw_status', 'waitlist');
            })
            ->get();

        if ($waitlistEntries->isEmpty()) {
            Notification::make()
                ->title('Random Draw Failed')
                ->body('No entries available in waitlist pool')
                ->warning()
                ->send();
            return;
        }

        if ($count > $waitlistEntries->count()) {
            $count = $waitlistEntries->count();
        }

        // Randomly select entries
        $selectedEntries = $waitlistEntries->random($count);

        $promoted = 0;
        $totalParticipants = 0;

        foreach ($selectedEntries as $entry) {
            $participantCount = $entry->isTeamEntry() ?
                $entry->getTeamMembers()->count() : 1;

            $this->promoteWaitlistEntry($entry, false);
            $promoted++;
            $totalParticipants += $participantCount;
        }

        Notification::make()
            ->title('Random Draw Completed')
            ->body("Randomly selected and promoted {$promoted} entries ({$totalParticipants} total participants) from waitlist")
            ->success()
            ->send();

        Log::info('Random draw from waitlist completed', [
            'entries_drawn' => $promoted,
            'total_participants' => $totalParticipants
        ]);
    }

    private function previewRandomDraw(int $targetParticipants, string $trackId = 'all'): void
    {
        // Build query with optional track filtering
        $query = WaitlistEntry::with(['registration.team'])
            ->whereHas('registration', function ($query) use ($trackId) {
                $query->where('draw_status', 'waitlist');
                if ($trackId !== 'all') {
                    $query->where('track_id', $trackId);
                }
            });

        $waitlistEntries = $query->get();

        if ($waitlistEntries->isEmpty()) {
            $trackInfo = $trackId === 'all' ? 'in waitlist pool' : 'for selected track';
            Notification::make()
                ->title('Preview Failed')
                ->body("No entries available {$trackInfo}")
                ->warning()
                ->send();
            return;
        }

        // Shuffle entries randomly
        $shuffledEntries = $waitlistEntries->shuffle();

        // Smart selection: try to get close to target participants without going way over
        $selectedEntries = collect();
        $totalParticipants = 0;
        $maxOverage = max(5, $targetParticipants * 0.2); // Allow 20% overage or minimum 5

        foreach ($shuffledEntries as $entry) {
            $entryParticipants = $entry->isTeamEntry()
                ? $entry->getTeamMembers()->count()
                : 1;

            // If adding this entry would exceed our target by too much, skip it
            if ($totalParticipants > 0 && ($totalParticipants + $entryParticipants) > ($targetParticipants + $maxOverage)) {
                continue;
            }

            $selectedEntries->push($entry);
            $totalParticipants += $entryParticipants;

            // Stop if we've reached a reasonable target
            if ($totalParticipants >= $targetParticipants) {
                break;
            }
        }

        if ($selectedEntries->isEmpty()) {
            Notification::make()
                ->title('Preview Failed')
                ->body('Unable to select entries that fit within participant target. Try increasing the target number.')
                ->warning()
                ->send();
            return;
        }

        // Store the selected entries for confirmation
        $this->previewedEntries = $selectedEntries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'name' => $entry->isTeamEntry()
                    ? $entry->registration->team->name . ' - ' . $entry->registration->name
                    : $entry->registration->name,
                'type' => $entry->isTeamEntry() ? 'Team' : 'Individual',
                'participant_count' => $entry->isTeamEntry()
                    ? $entry->getTeamMembers()->count()
                    : 1,
                'track' => $entry->registration->track_name,
                'email' => $entry->registration->email
            ];
        })->toArray();

        $actualParticipants = array_sum(array_column($this->previewedEntries, 'participant_count'));
        $entriesCount = count($this->previewedEntries);

        $trackInfo = $trackId === 'all' ? 'from all tracks' : "from selected track";

        Notification::make()
            ->title('Random Draw Preview Ready')
            ->body("Selected {$entriesCount} entries ({$actualParticipants} participants) {$trackInfo}. Target was {$targetParticipants} participants. Use 'Confirm & Execute Draw' to proceed or 'Cancel Preview' to start over.")
            ->success()
            ->send();
    }

    private function getConfirmationDescription(): string
    {
        if (empty($this->previewedEntries)) {
            return 'No entries selected for preview.';
        }

        $totalParticipants = array_sum(array_column($this->previewedEntries, 'participant_count'));
        $entriesCount = count($this->previewedEntries);

        // Get track information
        $trackInfo = $this->selectedTrackId === 'all' ? 'All Tracks' : 'Selected Track';
        if ($this->selectedTrackId !== 'all') {
            $tracks = app(\App\Settings\EventSettings::class)->tracks ?? [];
            $track = collect($tracks)->firstWhere('id', $this->selectedTrackId);
            if ($track) {
                $trackInfo = $track['name'] . (isset($track['distance']) ? ' (' . $track['distance'] . ' km)' : '');
            }
        }

        $description = "RANDOM DRAW CONFIRMATION\n\n";
        $description .= "Track Filter: {$trackInfo}\n";
        $description .= "Target Participants: {$this->targetParticipants}\n";
        $description .= "Actual Selection: {$entriesCount} entries ({$totalParticipants} participants)\n\n";
        $description .= "Selected entries for promotion:\n\n";

        foreach ($this->previewedEntries as $entry) {
            $description .= "• {$entry['name']} ({$entry['type']}, {$entry['participant_count']} participant(s), {$entry['track']})\n";
        }

        $description .= "\nThis action cannot be undone. Proceed with promoting these entries from waitlist to drawn status?";

        return $description;
    }

    private function executeConfirmedDraw(): void
    {
        if (empty($this->previewedEntries)) {
            Notification::make()
                ->title('Execution Failed')
                ->body('No preview entries found to execute')
                ->danger()
                ->send();
            return;
        }

        $promoted = 0;
        $totalParticipants = 0;

        // Get the actual waitlist entries by ID
        $entryIds = array_column($this->previewedEntries, 'id');
        $waitlistEntries = WaitlistEntry::with(['registration.team'])->whereIn('id', $entryIds)->get();

        foreach ($waitlistEntries as $entry) {
            $participantCount = $entry->isTeamEntry() ?
                $entry->getTeamMembers()->count() : 1;

            $this->promoteWaitlistEntry($entry, false);
            $promoted++;
            $totalParticipants += $participantCount;
        }

        // Clear the preview
        $this->previewedEntries = [];
        $trackInfo = $this->selectedTrackId === 'all' ? 'all tracks' : 'selected track';
        $this->selectedTrackId = 'all';
        $this->targetParticipants = 0;

        Notification::make()
            ->title('Random Draw Executed Successfully')
            ->body("Promoted {$promoted} entries ({$totalParticipants} total participants) from {$trackInfo} waitlist to drawn status")
            ->success()
            ->send();

        Log::info('Confirmed random draw from waitlist completed', [
            'entries_drawn' => $promoted,
            'total_participants' => $totalParticipants,
            'track_filter' => $trackInfo
        ]);
    }

    public function getWaitlistStats(): array
    {
        $waitlistEntries = WaitlistEntry::with(['registration.team'])
            ->whereHas('registration', function ($query) {
                $query->where('draw_status', 'waitlist');
            })
            ->get();

        $teams = $waitlistEntries->where('is_team_captain', true)->count();
        $individuals = $waitlistEntries->where('is_team_captain', false)->count();

        $totalParticipants = 0;
        foreach ($waitlistEntries as $entry) {
            $totalParticipants += $entry->isTeamEntry() ?
                $entry->getTeamMembers()->count() : 1;
        }

        // Get withdrawal stats per track
        $withdrawalStats = $this->getWithdrawalStats();
        
        // Get waitlist stats per track
        $trackStats = [];
        $tracks = app(\App\Settings\EventSettings::class)->tracks ?? [];
        
        foreach ($tracks as $track) {
            $trackWaitlistEntries = $waitlistEntries->filter(function ($entry) use ($track) {
                return $entry->registration->track_id == $track['id'];
            });
            
            $trackParticipants = 0;
            foreach ($trackWaitlistEntries as $entry) {
                $trackParticipants += $entry->isTeamEntry() ?
                    $entry->getTeamMembers()->count() : 1;
            }
            
            $trackStats[] = [
                'track_id' => $track['id'],
                'track_name' => $track['name'] . (isset($track['distance']) ? ' (' . $track['distance'] . ' km)' : ''),
                'waitlist_count' => $trackWaitlistEntries->count(),
                'waitlist_participants' => $trackParticipants,
                'withdrawal_count' => $withdrawalStats['per_track'][$track['id']] ?? 0,
            ];
        }

        return [
            'total_entries' => $waitlistEntries->count(),
            'teams' => $teams,
            'individuals' => $individuals,
            'total_participants' => $totalParticipants,
            'total_withdrawals' => $withdrawalStats['total'],
            'track_stats' => $trackStats,
        ];
    }
    
    public function getWithdrawalStats(): array
    {
        // Get registrations that have been withdrawn
        $withdrawnRegistrations = Registration::whereHas('withdrawalRequest', function ($query) {
            $query->where('is_withdrawn', true);
        })->get();
        
        $perTrack = [];
        $processedTeams = [];
        $totalWithdrawn = 0;
        
        foreach ($withdrawnRegistrations as $registration) {
            // Initialize track counter if needed
            if (!isset($perTrack[$registration->track_id])) {
                $perTrack[$registration->track_id] = 0;
            }
            
            // Handle team registrations
            if ($registration->team_id) {
                if (!in_array($registration->team_id, $processedTeams)) {
                    $processedTeams[] = $registration->team_id;
                    
                    // Count all team members that have been withdrawn
                    $teamWithdrawnCount = Registration::where('team_id', $registration->team_id)
                        ->whereHas('withdrawalRequest', function ($query) {
                            $query->where('is_withdrawn', true);
                        })
                        ->count();
                    
                    $perTrack[$registration->track_id] += $teamWithdrawnCount;
                    $totalWithdrawn += $teamWithdrawnCount;
                }
            } else {
                // Individual registration
                $perTrack[$registration->track_id]++;
                $totalWithdrawn++;
            }
        }
        
        return [
            'total' => $totalWithdrawn,
            'per_track' => $perTrack,
        ];
    }
}
