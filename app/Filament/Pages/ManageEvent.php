<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DailyRegistrations;
use App\Filament\Widgets\RegistrationStats;
use App\Filament\Widgets\RegistrationTimelineByGender;
use App\Filament\Widgets\RegistrationTimelineByTrack;
use App\Filament\Widgets\StateTransitionWidget;
use App\Filament\Widgets\TeamStats;
use App\Settings\EventSettings;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageEvent extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = -5;

    protected static string $settings = EventSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Details')
                    ->schema([
                        TextInput::make('event_name')
                            ->required(),
                        Select::make('application_state')
                            ->label('Current Application State')
                            ->options(EventSettings::getApplicationStates())
                            ->required()
                            ->helperText('Current state - may be overridden by automatic transitions')
                    ])
                    ->columns(2),

                Section::make('Organization / Club Information')
                    ->description('Configure your organization\'s branding and contact information')
                    ->schema([
                        TextInput::make('organization_name')
                            ->label('Organization Name')
                            ->required()
                            ->helperText('Full name of your club or organization (e.g., "Your Organization e.V.")'),

                        TextInput::make('organization_website')
                            ->label('Organization Website')
                            ->url()
                            ->required()
                            ->helperText('Your club\'s main website URL'),

                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->required()
                            ->helperText('Support email for participants to reach out'),

                        TextInput::make('organization_logo_path')
                            ->label('Logo Filename')
                            ->required()
                            ->helperText('Filename of your logo in the public directory (e.g., "logo.png")'),

                        TextInput::make('event_website_url')
                            ->label('Event Website URL')
                            ->url()
                            ->helperText('Specific page about this event (optional)'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Automatic State Management')
                    ->schema([
                        Toggle::make('automatic_state_transitions')
                            ->label('Enable Automatic State Transitions')
                            ->helperText('When enabled, application state will change automatically based on scheduled times')
                            ->reactive()
                            ->columnSpanFull(),

                        Toggle::make('manual_override_active')
                            ->label('Manual Override Active')
                            ->helperText('Temporarily override automatic transitions')
                            ->visible(fn($get) => $get('automatic_state_transitions'))
                            ->reactive(),

                        Select::make('manual_override_state')
                            ->label('Override State')
                            ->options(EventSettings::getApplicationStates())
                            ->visible(fn($get) => $get('automatic_state_transitions') && $get('manual_override_active'))
                            ->helperText('This state will be used instead of automatic transitions')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Registration Timeline')
                    ->schema([
                        DateTimePicker::make('flinta_registration_opens_at')
                            ->label('FLINTA* Registration Opens')
                            ->helperText('When registration opens for FLINTA* participants only')
                            ->seconds(false)
                            ->timezone(config('app.timezone')),

                        DateTimePicker::make('everyone_registration_opens_at')
                            ->label('Everyone Registration Opens')
                            ->helperText('When registration opens for all participants')
                            ->seconds(false)
                            ->timezone(config('app.timezone')),

                        DateTimePicker::make('registration_closes_at')
                            ->label('Registration Closes')
                            ->helperText('When new registrations are no longer accepted')
                            ->seconds(false)
                            ->timezone(config('app.timezone')),

                        DateTimePicker::make('waitlist_only_starts_at')
                            ->label('Waitlist Only Begins')
                            ->helperText('When only waitlist registrations are allowed')
                            ->seconds(false)
                            ->timezone(config('app.timezone')),

                        DateTimePicker::make('event_starts_at')
                            ->label('Event Starts')
                            ->helperText('When the actual event begins (switches to live mode)')
                            ->seconds(false)
                            ->timezone(config('app.timezone')),

                        DateTimePicker::make('event_ends_at')
                            ->label('Event Ends')
                            ->helperText('When the event officially ends (switches to closed)')
                            ->seconds(false)
                            ->timezone(config('app.timezone')),
                    ])
                    ->columns(2)
                    ->collapsible(),
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
            StateTransitionWidget::class,
            RegistrationStats::class,
            TeamStats::class,
            DailyRegistrations::class,
            RegistrationTimelineByTrack::class,
            RegistrationTimelineByGender::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }
}
