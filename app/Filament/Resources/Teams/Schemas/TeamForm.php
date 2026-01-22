<?php

namespace App\Filament\Resources\Teams\Schemas;

use App\Models\Team;
use App\Settings\EventSettings;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Team Information')
                    ->description('Basic team details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Team Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn ($record) => $record)
                            ->unique(Team::class, 'name', function ($rule, $get) {
                                $eventSettings = app(EventSettings::class);
                                if ($eventSettings->enforce_same_track_for_teams) {
                                    $trackId = $get('track_id');
                                    if ($trackId) {
                                        return $rule->where('track_id', $trackId);
                                    }
                                }
                                return $rule;
                            })
                            ->placeholder('Enter team name'),

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
                            ->placeholder(function () {
                                $eventSettings = app(EventSettings::class);
                                return $eventSettings->enforce_same_track_for_teams
                                    ? 'Select track for this team'
                                    : 'Optional - leave empty for mixed-track teams';
                            })
                            ->required(function (Get $get): bool {
                                $eventSettings = app(EventSettings::class);
                                return $eventSettings->enforce_same_track_for_teams;
                            })
                            ->helperText(function () {
                                $eventSettings = app(EventSettings::class);
                                return $eventSettings->enforce_same_track_for_teams
                                    ? 'All team members must be registered for this track'
                                    : 'Teams can have members on different tracks when this is empty';
                            }),

                        Forms\Components\TextInput::make('max_members')
                            ->label('Maximum Members')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(20)
                            ->default(5)
                            ->helperText('Maximum number of participants allowed in this team'),
                    ])->columns(2),

            ]);
    }
}
