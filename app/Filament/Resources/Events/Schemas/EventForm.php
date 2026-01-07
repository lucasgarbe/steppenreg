<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => 
                                $set('slug', Str::slug($state))
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'archived' => 'Archived',
                            ])
                            ->default('draft'),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Section::make('Registration Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('registration_opens_at')
                            ->label('Registration Opens'),
                        
                        Forms\Components\DateTimePicker::make('registration_closes_at')
                            ->label('Registration Closes'),
                        
                        Forms\Components\DatePicker::make('event_date')
                            ->label('Event Date'),
                    ])
                    ->columns(3),
                
                Section::make('Gender Categories')
                    ->schema([
                        Fieldset::make('FLINTA*')
                            ->schema([
                                Forms\Components\Toggle::make('settings.gender_categories.flinta.enabled')
                                    ->label('Enable FLINTA* Registration')
                                    ->default(true)
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('settings.gender_categories.flinta.label')
                                    ->label('Label')
                                    ->default('FLINTA*')
                                    ->maxLength(255),
                                
                                Forms\Components\DateTimePicker::make('settings.gender_categories.flinta.registration_opens_at')
                                    ->label('Registration Opens')
                                    ->helperText('When FLINTA* participants can start registering. Leave empty to use the event registration opens date.'),
                            ])
                            ->columns(2),
                        
                        Fieldset::make('Open/All Gender')
                            ->schema([
                                Forms\Components\Toggle::make('settings.gender_categories.all_gender.enabled')
                                    ->label('Enable Open/All Gender Registration')
                                    ->default(true)
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('settings.gender_categories.all_gender.label')
                                    ->label('Label')
                                    ->default('Open/All Gender')
                                    ->maxLength(255),
                                
                                Forms\Components\DateTimePicker::make('settings.gender_categories.all_gender.registration_opens_at')
                                    ->label('Registration Opens')
                                    ->helperText('When all participants can start registering. Leave empty to use the event registration opens date.'),
                            ])
                            ->columns(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->description('Configure when different gender categories can start registering for this event. This allows you to have separate registration opening times (e.g., FLINTA* registration opens 1 week before general registration).'),
            ]);
    }
}
