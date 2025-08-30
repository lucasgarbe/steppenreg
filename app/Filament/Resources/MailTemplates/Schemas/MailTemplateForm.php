<?php

namespace App\Filament\Resources\MailTemplates\Schemas;

use App\Models\MailTemplate;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section as SchemaSection;

class MailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make('Template Configuration')
                    ->schema([
                        Select::make('key')
                            ->label('Template Type')
                            ->options([
                                'registration_confirmation' => 'Registration Confirmation',
                                'draw_success' => 'Draw Success',
                                'draw_waitlist' => 'Draw Waitlist',
                                'draw_rejection' => 'Draw Rejection',
                            ])
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                SchemaSection::make('Email Content')
                    ->schema([
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        MarkdownEditor::make('body')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                                'heading',
                                'table'
                            ])
                    ]),

                SchemaSection::make('Available Variables')
                    ->schema([
                        Placeholder::make('variables_help')
                            ->label('')
                            ->content(function () {
                                $variables = MailTemplate::getAvailableVariables();
                                $help = "**Available Variables:**\n\n";

                                foreach ($variables as $key => $description) {
                                    $help .= "• `{{" . $key . "}}` - " . $description . "\n";
                                }

                                $help .= "\n**Markdown Formatting:**\n\n";
                                $help .= "• **Bold text** - `**bold**`\n";
                                $help .= "• *Italic text* - `*italic*`\n";
                                $help .= "• [Link text](https://example.com) - `[Link text](https://example.com)`\n";
                                $help .= "• [Email link](mailto:user@example.com) - `[Email link](mailto:user@example.com)`\n";
                                $help .= "• # Heading 1, ## Heading 2, ### Heading 3\n";
                                $help .= "• Bullet lists with `-` or `*`\n";
                                $help .= "• Numbered lists with `1.` `2.` etc.\n";
                                $help .= "• Tables:\n";
                                $help .= "  ```\n";
                                $help .= "  | Header 1 | Header 2 |\n";
                                $help .= "  |----------|----------|\n";
                                $help .= "  | Cell 1   | Cell 2   |\n";
                                $help .= "  ```\n";

                                return $help;
                            })
                            ->columnSpanFull(),


                    ])
                    ->collapsible(),
            ]);
    }
}
