<?php

namespace App\Filament\Pages;

use BackedEnum;
use EventSettings;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class ManageEvent extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = EventSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Details')
                    ->schema([
                        TextInput::make('event_name')
                            ->required(),
                        Toggle::make('site_active')
                            ->required(),
                    ]),
                Section::make('Tracks')
                    ->schema([
                        Repeater::make('tracks')
                            ->schema([
                                TextInput::make('id')
                                    ->required()
                                    ->numeric()
                                    ->label('Track ID'),
                                TextInput::make('name')
                                    ->required()
                                    ->label('Track Name'),
                                TextInput::make('distance')
                                    ->required()
                                    ->label('Distance (km)')
                                    ->numeric(),
                                TextInput::make('max_participants')
                                    ->label('Max Participants')
                                    ->numeric()
                                    ->nullable(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Add Track')
                            ->deleteAction(
                                fn($action) => $action->requiresConfirmation()
                            ),
                    ]),
            ]);
    }
}
