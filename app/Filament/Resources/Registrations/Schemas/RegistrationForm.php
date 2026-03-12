<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Participant Information')
                    ->description('Basic information about the participant')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter participant\'s full name'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('participant@example.com'),

                        Forms\Components\TextInput::make('age')
                            ->label('Age')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(120)
                            ->placeholder('Enter age'),

                        Forms\Components\Select::make('gender')
                            ->label('Gender Category')
                            ->options(fn () => \App\Models\Registration::getGenderOptionsForAdmin())
                            ->required()
                            ->searchable()
                            ->placeholder('Select gender category'),

                        Forms\Components\Select::make('track_id')
                            ->label(track_label())
                            ->options(function () {
                                $tracks = app(EventSettings::class)->tracks ?? [];
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
                            ->required()
                            ->placeholder('Select a track')
                            ->helperText('Choose the track/route for this participant'),

                        Forms\Components\Select::make('team_id')
                            ->label('Team Selection')
                            ->options(function (Get $get) {
                                $trackId = $get('track_id');
                                $eventSettings = app(EventSettings::class);

                                // When coupling is disabled, show all teams
                                if (! $eventSettings->enforce_same_track_for_teams) {
                                    return Team::notFull()
                                        ->withCount('registrations')
                                        ->get()
                                        ->mapWithKeys(function ($team) {
                                            $label = $team->name;
                                            $memberCount = $team->registrations_count;
                                            $maxMembers = $team->max_members;

                                            // Display format with unlimited support
                                            if ($maxMembers === null) {
                                                $label .= " ({$memberCount}/∞)";
                                            } else {
                                                $available = $maxMembers - $memberCount;
                                                $label .= " ({$memberCount}/{$maxMembers})";
                                                if ($available <= 2) {
                                                    $label .= " - {$available} spots left";
                                                }
                                            }

                                            if ($team->track_id) {
                                                $trackName = $team->track_name ?? track_label()." {$team->track_id}";
                                                $label .= " [{$trackName}]";
                                            }

                                            return [$team->id => $label];
                                        })
                                        ->toArray();
                                }

                                // When coupling is enabled, filter by track
                                if (! $trackId) {
                                    return [];
                                }

                                return Team::forTrack($trackId)
                                    ->notFull()
                                    ->withCount('registrations')
                                    ->get()
                                    ->mapWithKeys(function ($team) use ($trackId) {
                                        $label = $team->name;
                                        $memberCount = $team->registrations_count;
                                        $maxMembers = $team->max_members;

                                        // Display format with unlimited support
                                        if ($maxMembers === null) {
                                            $label .= " ({$memberCount}/∞)";
                                        } else {
                                            $available = $maxMembers - $memberCount;
                                            $label .= " ({$memberCount}/{$maxMembers})";
                                            if ($available <= 2) {
                                                $label .= " - {$available} spots left";
                                            }
                                        }

                                        // Show track name if team has different track
                                        if ($team->track_id && $team->track_id !== $trackId) {
                                            $trackName = $team->track_name ?? track_label()." {$team->track_id}";
                                            $label .= " [{$trackName}]";
                                        }

                                        return [$team->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Select a team (optional)')
                            ->helperText(function () {
                                $eventSettings = app(EventSettings::class);

                                return $eventSettings->enforce_same_track_for_teams
                                    ? 'Only teams for your selected track are shown'
                                    : 'All teams are available (teams can have members on different tracks)';
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Team::class, 'name', function ($rule, $get) {
                                        $eventSettings = app(EventSettings::class);
                                        if ($eventSettings->enforce_same_track_for_teams) {
                                            return $rule->where('track_id', $get('../../track_id'));
                                        }

                                        return $rule;
                                    }),
                                Forms\Components\TextInput::make('max_members')
                                    ->numeric()
                                    ->nullable()
                                    ->default(fn () => app(EventSettings::class)->default_team_max_members)
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->placeholder('Unlimited'),
                            ])
                            ->createOptionUsing(function (array $data, Get $get): int {
                                $eventSettings = app(EventSettings::class);
                                $team = Team::create([
                                    'name' => $data['name'],
                                    'max_members' => $data['max_members'] ?? $eventSettings->default_team_max_members,
                                    'track_id' => $eventSettings->enforce_same_track_for_teams
                                        ? $get('track_id')
                                        : null,
                                ]);

                                return $team->id;
                            }),
                    ])->columns(2),

                Section::make('Registration Status')
                    ->description('Payment and participation status')
                    ->schema([
                        Forms\Components\Select::make('draw_status')
                            ->label('Draw Status')
                            ->options([
                                'not_drawn' => 'Not Drawn',
                                'drawn' => 'Drawn',
                            ])
                            ->default('not_drawn')
                            ->required()
                            ->helperText('Current draw status of this registration'),

                        Forms\Components\Toggle::make('payed')
                            ->label('Payment Received')
                            ->helperText('Mark as paid when payment is confirmed'),

                        Forms\Components\Toggle::make('starting')
                            ->label('Confirmed Starting')
                            ->helperText('Participant confirmed to start the event'),

                        Forms\Components\TimePicker::make('finish_time')
                            ->label('Finish Time')
                            ->helperText('Record finish time when participant completes the event')
                            ->seconds(false),
                    ])->columns(2),

                Section::make('Starting Number')
                    ->description('Manually assign a starting number to this registration')
                    ->schema([
                        Forms\Components\TextInput::make('starting_number_manual')
                            ->label('Starting Number')
                            ->numeric()
                            ->minValue(1)
                            ->integer()
                            ->placeholder('Leave empty to remove / not assign')
                            ->helperText('Manually assign a starting number. Must be unique across all registrations.')
                            ->rules(fn (?Registration $record): array => [
                                Rule::unique('starting_numbers', 'number')
                                    ->ignore($record?->startingNumber?->id),
                            ])
                            ->dehydrated(false),
                    ])
                    ->visible(fn () => config('steppenreg.features.starting_numbers', true))
                    ->visibleOn('edit'),

                Section::make('Additional Information')
                    ->description('Notes and additional details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Any additional notes about this registration...'),
                    ]),

                Section::make('Custom Questions')
                    ->description('Event-specific questions and answers')
                    ->schema(function () {
                        $eventSettings = app(EventSettings::class);
                        $customQuestions = $eventSettings->custom_questions ?? [];
                        $fields = [];

                        foreach ($customQuestions as $question) {
                            $key = $question['key'];
                            $currentLocale = app()->getLocale();
                            $fullLabel = $question['translations'][$currentLocale]['label']
                                ?? $question['translations']['en']['label']
                                ?? $key;

                            $field = match ($question['type']) {
                                'text', 'email' => Forms\Components\TextInput::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->required($question['required'] ?? false),

                                'textarea' => Forms\Components\Textarea::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->rows(3)
                                    ->required($question['required'] ?? false),

                                'number' => Forms\Components\TextInput::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->numeric()
                                    ->required($question['required'] ?? false),

                                'select' => Forms\Components\Select::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->options(collect($question['options'] ?? [])
                                        ->pluck('label_en', 'value')
                                        ->toArray())
                                    ->required($question['required'] ?? false),

                                'radio' => Forms\Components\Radio::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->options(collect($question['options'] ?? [])
                                        ->pluck('label_en', 'value')
                                        ->toArray())
                                    ->required($question['required'] ?? false),

                                'checkbox' => Forms\Components\CheckboxList::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->options(collect($question['options'] ?? [])
                                        ->pluck('label_en', 'value')
                                        ->toArray())
                                    ->required($question['required'] ?? false),

                                'date' => Forms\Components\DatePicker::make("custom_answers.{$key}")
                                    ->label($key)
                                    ->helperText($fullLabel)
                                    ->required($question['required'] ?? false),

                                default => null
                            };

                            if ($field) {
                                $fields[] = $field;
                            }
                        }

                        return $fields;
                    })
                    ->columns(2)
                    ->visible(fn () => ! empty(app(EventSettings::class)->custom_questions)),
            ]);
    }
}
