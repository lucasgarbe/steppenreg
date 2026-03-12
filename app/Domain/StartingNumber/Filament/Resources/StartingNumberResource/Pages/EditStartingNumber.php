<?php

namespace App\Domain\StartingNumber\Filament\Resources\StartingNumberResource\Pages;

use App\Domain\StartingNumber\Filament\Resources\StartingNumberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStartingNumber extends EditRecord
{
    protected static string $resource = StartingNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
