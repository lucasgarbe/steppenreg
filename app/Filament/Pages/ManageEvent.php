<?php

namespace App\Filament\Pages;

use App\Settings\EventSettings;
use App\Filament\Widgets\DailyRegistrations;
use App\Filament\Widgets\RegistrationTimelineByGender;
use App\Filament\Widgets\RegistrationTimelineByTrack;
use App\Filament\Widgets\RegistrationStats;
use App\Filament\Widgets\TeamStats;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                        Select::make('application_state')
                            ->label('Application State')
                            ->options(EventSettings::getApplicationStates())
                            ->required()
                            ->helperText('Controls what type of registrations are accepted')
                            ->native(false),
                    ])
                    ->columns(2),
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
    
    public function getWidgets(): array
    {
        return [
            RegistrationStats::class,
            TeamStats::class,
            DailyRegistrations::class,
            RegistrationTimelineByTrack::class,
            RegistrationTimelineByGender::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 3;
    }
}
