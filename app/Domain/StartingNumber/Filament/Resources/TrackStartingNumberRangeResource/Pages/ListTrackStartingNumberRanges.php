<?php

namespace App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource\Pages;

use App\Domain\StartingNumber\Filament\Resources\TrackStartingNumberRangeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackStartingNumberRanges extends ListRecords
{
    protected static string $resource = TrackStartingNumberRangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
