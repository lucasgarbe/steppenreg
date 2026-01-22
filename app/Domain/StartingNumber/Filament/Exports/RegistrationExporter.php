<?php

namespace App\Domain\StartingNumber\Filament\Exports;

use App\Filament\Exports\RegistrationExporter as BaseExporter;
use App\Models\Registration;
use Filament\Actions\Exports\ExportColumn;

class RegistrationExporter extends BaseExporter
{
    public static function getColumns(): array
    {
        $baseColumns = parent::getColumns();

        if (! config('steppenreg.features.starting_numbers', true)) {
            return $baseColumns;
        }

        $startingNumberColumn = ExportColumn::make('starting_number')
            ->label('Starting Number')
            ->state(function (Registration $record) {
                if (! $record->starting_number) {
                    return '---';
                }

                $service = app(\App\Domain\StartingNumber\Services\StartingNumberService::class);

                return $service->getNumberLabel($record);
            });

        $startingNumberColumn->enabledByDefault(true);

        array_splice($baseColumns, 1, 0, [$startingNumberColumn]);

        return $baseColumns;
    }
}
