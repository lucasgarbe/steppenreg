<?php

namespace App\Filament\Resources\MailTemplates\Schemas;

use App\Models\MailTemplate;
use Filament\Forms\Components\RichEditor;
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
                        
                        RichEditor::make('body')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                            ]),
                    ]),
                
                SchemaSection::make('Available Variables')
                    ->schema([
                        Placeholder::make('variables_help')
                            ->label('')
                            ->content(function () {
                                $variables = MailTemplate::getAvailableVariables();
                                $help = "You can use these variables in your subject and body:\n\n";
                                
                                foreach ($variables as $key => $description) {
                                    $help .= "• {{" . $key . "}} - " . $description . "\n";
                                }
                                
                                return $help;
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
