<?php

namespace App\Filament\Pages;

use App\Settings\EventSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageCustomQuestions extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = -4;

    protected static string $settings = EventSettings::class;

    protected static ?string $title = 'Custom Questions';

    protected static ?string $navigationLabel = 'Custom Questions';

    public function form(Schema $schema): Schema
    {
        $availableLocales = EventSettings::getAvailableLocales();

        return $schema
            ->components([
                Section::make('Registration Questions')
                    ->description('Configure additional questions for the registration form. Questions will appear in the order defined here.')
                    ->schema([
                        Repeater::make('custom_questions')
                            ->label('')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Question Key')
                                    ->required()
                                    ->helperText('Unique identifier (e.g., emergency_contact, shirt_size). Use lowercase, underscores only.')
                                    ->regex('/^[a-z0-9_]+$/')
                                    ->maxLength(50)
                                    ->columnSpanFull(),

                                Select::make('type')
                                    ->label('Question Type')
                                    ->options(EventSettings::getQuestionTypes())
                                    ->required()
                                    ->reactive()
                                    ->helperText('Choose the input type for this question'),

                                Toggle::make('required')
                                    ->label('Required')
                                    ->default(false)
                                    ->helperText('Mark as required field'),

                                Section::make('Translations')
                                    ->description('Configure labels for all languages')
                                    ->schema(
                                        collect($availableLocales)->flatMap(function ($locale) {
                                            return [
                                                TextInput::make("translations.{$locale}.label")
                                                    ->label(strtoupper($locale).' Label')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->helperText("Question label ({$locale}). Supports Markdown: [link](https://url.com), **bold**, *italic*, <u>underline</u>"),

                                                TextInput::make("translations.{$locale}.placeholder")
                                                    ->label(strtoupper($locale).' Placeholder')
                                                    ->maxLength(255)
                                                    ->helperText("Placeholder text ({$locale})"),

                                                Textarea::make("translations.{$locale}.help")
                                                    ->label(strtoupper($locale).' Help Text')
                                                    ->maxLength(500)
                                                    ->rows(2)
                                                    ->helperText("Optional help text ({$locale}). Supports Markdown: [link](https://url.com), **bold**, *italic*, <u>underline</u>")
                                                    ->columnSpanFull(),
                                            ];
                                        })->toArray()
                                    )
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->collapsible(),

                                Section::make('Error Messages')
                                    ->description('Configure custom validation error messages (optional). If not set, generic messages will be used.')
                                    ->schema(
                                        collect($availableLocales)->flatMap(function ($locale) {
                                            return [
                                                TextInput::make("translations.{$locale}.error_required")
                                                    ->label(strtoupper($locale).' Required Error')
                                                    ->maxLength(255)
                                                    ->placeholder($locale === 'de' ? 'z.B. Bitte akzeptiere die Bedingungen.' : 'e.g. Please accept the terms.')
                                                    ->helperText("Message when field is empty (optional)"),

                                                TextInput::make("translations.{$locale}.error_invalid")
                                                    ->label(strtoupper($locale).' Invalid Error')
                                                    ->maxLength(255)
                                                    ->placeholder($locale === 'de' ? 'z.B. Bitte gib einen gültigen Wert ein.' : 'e.g. Please enter a valid value.')
                                                    ->helperText("Message for type validation (email, number, date) (optional)"),

                                                TextInput::make("translations.{$locale}.error_max")
                                                    ->label(strtoupper($locale).' Too Long Error')
                                                    ->maxLength(255)
                                                    ->placeholder($locale === 'de' ? 'z.B. Die Eingabe ist zu lang.' : 'e.g. The input is too long.')
                                                    ->helperText("Message when input exceeds maximum length (optional)")
                                                    ->visible(fn (Get $get): bool => in_array($get('type'), ['text', 'textarea', 'email']))
                                                    ->columnSpanFull(),
                                            ];
                                        })->toArray()
                                    )
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->collapsed(),

                                Repeater::make('options')
                                    ->label('Answer Options')
                                    ->schema(
                                        array_merge(
                                            [
                                                TextInput::make('value')
                                                    ->label('Option Value')
                                                    ->required()
                                                    ->helperText('Internal value (e.g., "s", "m", "l")')
                                                    ->maxLength(100),
                                            ],
                                            collect($availableLocales)->map(function ($locale) {
                                                return TextInput::make("label_{$locale}")
                                                    ->label(strtoupper($locale).' Label')
                                                    ->required()
                                                    ->helperText("Display label ({$locale})");
                                            })->toArray()
                                        )
                                    )
                                    ->columns(1 + count($availableLocales))
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Option')
                                    ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'radio', 'checkbox']))
                                    ->helperText('Define the available choices for this question')
                                    ->columnSpanFull(),

                                Textarea::make('validation')
                                    ->label('Additional Validation Rules')
                                    ->helperText('Laravel validation rules (e.g., "min:3|max:100|regex:/^[0-9]+$/"). One per line or comma-separated.')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['translations']['en']['label'] ?? $state['key'] ?? null).
                                ' ('.($state['type'] ?? 'unknown').')'
                            )
                            ->addActionLabel('Add Question')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                            )
                            ->defaultItems(0),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure validation is a string for display
        if (isset($data['custom_questions'])) {
            foreach ($data['custom_questions'] as $index => $question) {
                if (isset($question['validation']) && is_array($question['validation'])) {
                    $data['custom_questions'][$index]['validation'] = implode("\n", $question['validation']);
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert validation string to array
        if (isset($data['custom_questions'])) {
            foreach ($data['custom_questions'] as $index => $question) {
                if (isset($question['validation']) && is_string($question['validation'])) {
                    $rules = array_filter(
                        array_map(
                            'trim',
                            preg_split('/[\n,]+/', $question['validation'])
                        )
                    );
                    $data['custom_questions'][$index]['validation'] = $rules;
                }

                // Add sort_order based on position
                $data['custom_questions'][$index]['sort_order'] = $index + 1;
            }
        }

        return $data;
    }
}
