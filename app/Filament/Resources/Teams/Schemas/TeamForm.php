<?php

namespace App\Filament\Resources\Teams\Schemas;

use App\Settings\EventSettings;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

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
                            ->unique(ignorable: fn($record) => $record)
                            ->placeholder('Enter team name'),

                        Forms\Components\Select::make('track_id')
                            ->label('Team Track')
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
                            ->placeholder('Select track for this team')
                            ->helperText('All team members must be registered for this track'),

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
