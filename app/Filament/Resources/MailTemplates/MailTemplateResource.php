<?php

namespace App\Filament\Resources\MailTemplates;

use App\Filament\Resources\MailTemplates\Pages\CreateMailTemplate;
use App\Filament\Resources\MailTemplates\Pages\EditMailTemplate;
use App\Filament\Resources\MailTemplates\Pages\ListMailTemplates;
use App\Filament\Resources\MailTemplates\Schemas\MailTemplateForm;
use App\Filament\Resources\MailTemplates\Tables\MailTemplatesTable;
use App\Models\MailTemplate;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MailTemplateResource extends Resource
{
    protected static ?string $model = MailTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = 'Mail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    
    protected static ?string $navigationLabel = 'Templates';
    
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return MailTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailTemplates::route('/'),
            'create' => CreateMailTemplate::route('/create'),
            'edit' => EditMailTemplate::route('/{record}/edit'),
        ];
    }
}
