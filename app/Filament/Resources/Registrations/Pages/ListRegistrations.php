<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageDraw')
                ->label('Draw Management')
                ->icon('heroicon-o-star')
                ->color('primary')
                ->url(static::$resource::getUrl('draw')),
            CreateAction::make(),
        ];
    }
}
