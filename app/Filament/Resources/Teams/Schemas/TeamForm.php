<?php

namespace App\Filament\Resources\Teams\Schemas;

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
