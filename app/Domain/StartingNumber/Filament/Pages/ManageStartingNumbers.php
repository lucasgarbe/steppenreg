<?php

namespace App\Domain\StartingNumber\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Config;
use UnitEnum;

class ManageStartingNumbers extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Starting Numbers';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Manage Starting Numbers';

    protected string $view = 'filament.starting-numbers.pages.manage-starting-numbers';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tracks' => $this->getTracksConfiguration(),
            'overflow_enabled' => config('starting-numbers.overflow.enabled', true),
            'overflow_start' => config('starting-numbers.overflow.start', 9001),
            'overflow_end' => config('starting-numbers.overflow.end', 9999),
            'padding' => config('starting-numbers.format.padding', 4),
            'prefix' => config('starting-numbers.format.prefix', ''),
            'suffix' => config('starting-numbers.format.suffix', ''),
            'strategy' => config('starting-numbers.strategy', 'sequential'),
            'reserved' => config('starting-numbers.reserved', []),
            'auto_assign' => config('starting-numbers.auto_assign', true),
            'allow_manual_override' => config('starting-numbers.allow_manual_override', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Track Number Ranges')
                    ->description('Configure starting number ranges for each track. Numbers are assigned from these ranges when registrations are drawn.')
                    ->schema([
                        Repeater::make('tracks')
                            ->schema([
                                TextInput::make('track_id')
                                    ->label('Track ID')
                                    ->required()
                                    ->numeric()
                                    ->helperText('The database ID of the track'),

                                TextInput::make('name')
                                    ->label('Track Name')
                                    ->required()
                                    ->helperText('Display name for reference'),

                                TextInput::make('start')
                                    ->label('Start Number')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('First number in this range'),

                                TextInput::make('end')
                                    ->label('End Number')
                                    ->required()
                                    ->numeric()
                                    ->minValue(fn ($get) => $get('start') ?? 1)
                                    ->helperText('Last number in this range'),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Add Track Range')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->helperText('Add a number range for each track in your event'),
                    ]),

                Section::make('Overflow Bucket')
                    ->description('Global overflow range used when track-specific ranges are exhausted.')
                    ->schema([
                        Toggle::make('overflow_enabled')
                            ->label('Enable Overflow Bucket')
                            ->helperText('Use a fallback range when track numbers run out')
                            ->reactive(),

                        TextInput::make('overflow_start')
                            ->label('Overflow Start')
                            ->required(fn ($get) => $get('overflow_enabled'))
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn ($get) => $get('overflow_enabled'))
                            ->helperText('First overflow number'),

                        TextInput::make('overflow_end')
                            ->label('Overflow End')
                            ->required(fn ($get) => $get('overflow_enabled'))
                            ->numeric()
                            ->minValue(fn ($get) => $get('overflow_start') ?? 1)
                            ->visible(fn ($get) => $get('overflow_enabled'))
                            ->helperText('Last overflow number'),
                    ])
                    ->columns(3),

                Section::make('Number Formatting')
                    ->description('Configure how starting numbers are displayed and formatted.')
                    ->schema([
                        TextInput::make('padding')
                            ->label('Padding (Digits)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(4)
                            ->helperText('Number of digits with leading zeros (e.g., 4 = 0001)'),

                        TextInput::make('prefix')
                            ->label('Prefix')
                            ->maxLength(10)
                            ->helperText('Text before number (e.g., "BIB-")'),

                        TextInput::make('suffix')
                            ->label('Suffix')
                            ->maxLength(10)
                            ->helperText('Text after number (e.g., "-A")'),

                        TextInput::make('preview')
                            ->label('Preview')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($get) {
                                return $this->formatNumberPreview(42, $get);
                            })
                            ->reactive()
                            ->helperText('Preview of formatted number'),
                    ])
                    ->columns(4),

                Section::make('Assignment Options')
                    ->description('Configure how numbers are assigned to registrations.')
                    ->schema([
                        Select::make('strategy')
                            ->label('Assignment Strategy')
                            ->options([
                                'sequential' => 'Sequential (1, 2, 3, ...)',
                                'random' => 'Random from available',
                            ])
                            ->required()
                            ->default('sequential')
                            ->helperText('How to select numbers from the range'),

                        TagsInput::make('reserved')
                            ->label('Reserved Numbers')
                            ->helperText('Numbers to never assign automatically (e.g., unlucky numbers)')
                            ->placeholder('Enter numbers and press Enter'),

                        Toggle::make('auto_assign')
                            ->label('Auto-Assign on Draw')
                            ->helperText('Automatically assign numbers when registrations are drawn')
                            ->default(true),

                        Toggle::make('allow_manual_override')
                            ->label('Allow Manual Override')
                            ->helperText('Allow admins to manually change assigned numbers')
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function formatNumberPreview(int $number, callable $get): string
    {
        $padding = (int) ($get('padding') ?? 4);
        $prefix = $get('prefix') ?? '';
        $suffix = $get('suffix') ?? '';

        return $prefix . str_pad((string) $number, $padding, '0', STR_PAD_LEFT) . $suffix;
    }

    protected function getTracksConfiguration(): array
    {
        $tracks = config('starting-numbers.tracks', []);

        // Convert associative array to repeater format
        $result = [];
        foreach ($tracks as $trackId => $config) {
            $result[] = [
                'track_id' => $trackId,
                'name' => $config['name'] ?? "Track {$trackId}",
                'start' => $config['start'] ?? 1,
                'end' => $config['end'] ?? 100,
            ];
        }

        return $result;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Validate no overlapping ranges
        if (! $this->validateRanges($data)) {
            Notification::make()
                ->title('Validation Error')
                ->body('Track number ranges cannot overlap. Please check your configuration.')
                ->danger()
                ->send();

            return;
        }

        // Convert repeater data back to associative array
        $tracksConfig = [];
        foreach ($data['tracks'] as $track) {
            $tracksConfig[$track['track_id']] = [
                'name' => $track['name'],
                'start' => (int) $track['start'],
                'end' => (int) $track['end'],
            ];
        }

        // Update configuration
        $configPath = config_path('starting-numbers.php');

        $configContent = "<?php\n\nreturn " . var_export([
            'enabled' => config('starting-numbers.enabled', true),
            'tracks' => $tracksConfig,
            'overflow' => [
                'enabled' => $data['overflow_enabled'],
                'start' => (int) $data['overflow_start'],
                'end' => (int) $data['overflow_end'],
            ],
            'format' => [
                'padding' => (int) $data['padding'],
                'prefix' => $data['prefix'] ?? '',
                'suffix' => $data['suffix'] ?? '',
            ],
            'strategy' => $data['strategy'],
            'reserved' => array_map('intval', $data['reserved'] ?? []),
            'auto_assign' => $data['auto_assign'],
            'allow_manual_override' => $data['allow_manual_override'],
        ], true) . ";\n";

        // Write to file
        file_put_contents($configPath, $configContent);

        // Clear config cache
        if (app()->configurationIsCached()) {
            Notification::make()
                ->title('Configuration Saved')
                ->body('Settings saved successfully. Please run `php artisan config:cache` to apply changes.')
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Configuration Saved')
                ->body('Starting number settings have been saved successfully.')
                ->success()
                ->send();
        }
    }

    protected function validateRanges(array $data): bool
    {
        $ranges = [];

        // Collect all track ranges
        foreach ($data['tracks'] as $track) {
            $ranges[] = [
                'start' => (int) $track['start'],
                'end' => (int) $track['end'],
                'name' => $track['name'],
            ];
        }

        // Add overflow if enabled
        if ($data['overflow_enabled']) {
            $ranges[] = [
                'start' => (int) $data['overflow_start'],
                'end' => (int) $data['overflow_end'],
                'name' => 'Overflow',
            ];
        }

        // Check for overlaps
        for ($i = 0; $i < count($ranges); $i++) {
            for ($j = $i + 1; $j < count($ranges); $j++) {
                $r1 = $ranges[$i];
                $r2 = $ranges[$j];

                // Check if ranges overlap
                if ($r1['start'] <= $r2['end'] && $r2['start'] <= $r1['end']) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function getCachedFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Configuration')
                ->submit('save')
                ->color('primary'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_starting_numbers') ?? auth()->check();
    }
}
