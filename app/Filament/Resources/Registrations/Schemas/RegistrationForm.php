<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Models\Team;
use EventSettings;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

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
                            ->unique(ignorable: fn($record) => $record)
                            ->placeholder('participant@example.com'),

                        Forms\Components\TextInput::make('age')
                            ->label('Age')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(120)
                            ->placeholder('Enter age'),

                        Forms\Components\Select::make('track_id')
                            ->label('Track Selection')
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
                            ->required()
                            ->placeholder('Select a track')
                            ->helperText('Choose the track/route for this participant'),

                        Forms\Components\Select::make('team_id')
                            ->label('Team Selection')
                            ->options(function () {
                                return Team::notFull()
                                    ->withCount('registrations')
                                    ->get()
                                    ->mapWithKeys(function ($team) {
                                        $label = $team->name;
                                        $memberCount = $team->registrations_count;
                                        $maxMembers = $team->max_members;
                                        $available = $maxMembers - $memberCount;
                                        
                                        $label .= " ({$memberCount}/{$maxMembers})";
                                        if ($available <= 2) {
                                            $label .= " - {$available} spots left";
                                        }
                                        
                                        return [$team->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('Select a team (optional)')
                            ->helperText('Choose an existing team or leave empty for individual registration')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Team::class, 'name'),
                                Forms\Components\TextInput::make('max_members')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(1)
                                    ->maxValue(20),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $team = Team::create($data);
                                return $team->id;
                            }),
                    ])->columns(2),

                Section::make('Registration Status')
                    ->description('Payment and participation status')
                    ->schema([
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
                    ])->columns(3),

                Section::make('Additional Information')
                    ->description('Notes and additional details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Any additional notes about this registration...'),
                    ]),
            ]);
    }
}
