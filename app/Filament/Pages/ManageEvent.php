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
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->helperText('Current state - may be overridden by automatic transitions'),
                    ])
                    ->columns(2),

                Section::make('Organization / Club Information')
                    ->description('Configure your organization\'s branding and contact information')
                    ->schema([
                        TextInput::make('organization_name')
                            ->label('Organization Name')
                            ->required()
                            ->helperText('Full name of your club or organization (e.g., "RSV Steppenwolf 2023 e.V.")'),

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
                            ->visible(fn ($get) => $get('automatic_state_transitions'))
                            ->reactive(),

                        Select::make('manual_override_state')
                            ->label('Override State')
                            ->options(EventSettings::getApplicationStates())
                            ->visible(fn ($get) => $get('automatic_state_transitions') && $get('manual_override_active'))
                            ->helperText('This state will be used instead of automatic transitions')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Registration Timeline')
                    ->description('Gender category registration times are configured in the Gender Categories section below.')
                    ->schema([
                        DateTimePicker::make('registration_closes_at')
                            ->label('Registration Closes')
                            ->helperText('When new registrations are no longer accepted')
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

                Section::make('Theme Colors')
                    ->description('Customize the colors used on public-facing pages and forms')
                    ->schema([
                        ColorPicker::make('theme_primary_color')
                            ->label('Primary Color')
                            ->helperText('Used for buttons, form focus states, and active elements')
                            ->default('#F9C458'),

                        ColorPicker::make('theme_background_color')
                            ->label('Background Color')
                            ->helperText('Page background color')
                            ->default('#fffdf8c2'),

                        ColorPicker::make('theme_text_color')
                            ->label('Text & Border Color')
                            ->helperText('Primary text color and container borders')
                            ->default('#1a1a1a'),

                        ColorPicker::make('theme_accent_color')
                            ->label('Secondary Accent Color')
                            ->helperText('Secondary accent for additional highlights')
                            ->default('#7a58fc'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Custom Track Labels')
                    ->description('Customize the term "Track" displayed throughout the site. Leave empty to use default translations (Track/Strecke).')
                    ->schema([
                        TextInput::make('track_label_singular_en')
                            ->label('Track Label - Singular (English)')
                            ->placeholder('Track')
                            ->helperText('Custom label for singular form in English. Leave empty for default: "Track"'),

                        TextInput::make('track_label_singular_de')
                            ->label('Track Label - Singular (German)')
                            ->placeholder('Strecke')
                            ->helperText('Custom label for singular form in German. Leave empty for default: "Strecke"'),

                        TextInput::make('track_label_plural_en')
                            ->label('Track Label - Plural (English)')
                            ->placeholder('Tracks')
                            ->helperText('Custom label for plural form in English. Leave empty for default: "Tracks"'),

                        TextInput::make('track_label_plural_de')
                            ->label('Track Label - Plural (German)')
                            ->placeholder('Strecken')
                            ->helperText('Custom label for plural form in German. Leave empty for default: "Strecken"'),
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
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Add Track')
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                            ),
                    ]),

                Section::make('Gender Categories')
                    ->description('Configure gender categories with priority access and custom messaging. Priority categories open first, then general categories.')
                    ->schema([
                        Repeater::make('gender_categories')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Category Key')
                                    ->required()
                                    ->helperText('Unique identifier (lowercase, underscores only). Cannot be changed once registrations exist.')
                                    ->regex('/^[a-z0-9_]+$/')
                                    ->maxLength(50)
                                    ->columnSpan(2),

                                Toggle::make('is_priority')
                                    ->label('Priority')
                                    ->helperText('Gets early access during priority registration period')
                                    ->default(false)
                                    ->reactive()
                                    ->columnSpan(1),

                                ColorPicker::make('color')
                                    ->label('Badge Color')
                                    ->required()
                                    ->helperText('Color for badges, charts, and visual indicators')
                                    ->columnSpan(1),

                                DateTimePicker::make('registration_opens_at')
                                    ->label('Registration Opens')
                                    ->required(fn (Get $get) => $get('../../automatic_state_transitions') ?? false)
                                    ->helperText(function (Get $get): string {
                                        $isPriority = $get('is_priority');
                                        $root = $get('../../');
                                        $autoTransitions = $root['automatic_state_transitions'] ?? false;

                                        if ($autoTransitions) {
                                            $type = $isPriority ? 'priority' : 'general';

                                            return "Required. When this {$type} category opens. Priority must open before general.";
                                        }

                                        return 'Optional. Set for automatic state transitions.';
                                    })
                                    ->seconds(false)
                                    ->timezone(config('app.timezone'))
                                    ->columnSpan(2),

                                Section::make('Translations')
                                    ->description('Category labels for each language')
                                    ->schema([
                                        TextInput::make('translations.en.label')
                                            ->label('English Label')
                                            ->required()
                                            ->maxLength(100),

                                        TextInput::make('translations.de.label')
                                            ->label('German Label')
                                            ->required()
                                            ->maxLength(100),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->columnSpanFull(),

                                Section::make('Priority Period Message')
                                    ->description('Optional custom message shown when this category is available during priority period. Supports rich text formatting.')
                                    ->schema([
                                        RichEditor::make('message.en')
                                            ->label('English Message')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                            ])
                                            ->helperText('Shown to users who can access this category')
                                            ->maxLength(2000),

                                        RichEditor::make('message.de')
                                            ->label('German Message')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                            ])
                                            ->helperText('German version of the message')
                                            ->maxLength(2000),

                                        Select::make('message_style')
                                            ->label('Message Style')
                                            ->options([
                                                'info' => 'Info (Blue)',
                                                'success' => 'Success (Green)',
                                                'warning' => 'Warning (Yellow)',
                                                'pride' => 'Pride (Rainbow)',
                                            ])
                                            ->default('info')
                                            ->helperText('Visual styling for the message box')
                                            ->native(false),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state): ?string => ($state['is_priority'] ?? false ? '[Priority] ' : '').($state['translations']['en']['label'] ?? $state['key'] ?? null))
                            ->addActionLabel('Add Gender Category')
                            ->deleteAction(
                                fn ($action) => $action
                                    ->requiresConfirmation()
                                    ->modalDescription('This may affect existing registrations with this category.')
                            )
                            ->defaultItems(0),
                    ])
                    ->collapsible(),
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Add sort_order to gender categories based on position
        if (isset($data['gender_categories'])) {
            foreach ($data['gender_categories'] as $index => $category) {
                $data['gender_categories'][$index]['sort_order'] = $index + 1;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Validate category opening times
        $eventSettings = app(EventSettings::class);
        $errors = $eventSettings->validateCategoryOpeningTimes();

        if (! empty($errors)) {
            \Filament\Notifications\Notification::make()
                ->title('Category Opening Time Validation')
                ->body(implode(' ', $errors))
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
