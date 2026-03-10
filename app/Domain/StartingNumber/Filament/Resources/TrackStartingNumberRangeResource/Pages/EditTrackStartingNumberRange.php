<?php

namespace App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource\Pages;

use App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrackStartingNumberRange extends EditRecord
{
    protected static string $resource = TrackStartingNumberRangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
