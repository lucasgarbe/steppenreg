<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Domain\StartingNumber\Events\StartingNumberAssigned;
use App\Domain\StartingNumber\Models\Bib;
use App\Domain\StartingNumber\Models\StartingNumber;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Filament\Resources\Registrations\RegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRegistration extends EditRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['starting_number_manual'] = $this->getRecord()->startingNumber?->number;

        return $data;
    }

    protected function afterSave(): void
    {
        if (! config('steppenreg.features.starting_numbers', true)) {
            return;
        }

        $registration = $this->getRecord();
        $service = app(StartingNumberService::class);

        $existing = $registration->startingNumber;
        $newNumber = $this->data['starting_number_manual'] ?? null;

        if (blank($newNumber)) {
            if ($existing) {
                $service->clearNumber($registration);
            }

            return;
        }

        $newNumber = (int) $newNumber;

        if ($existing) {
            if ($existing->number !== $newNumber) {
                // Point the assignment at a different bib (create if needed)
                $bib = Bib::firstOrCreate(
                    ['number' => $newNumber]
                );
                $existing->update(['bib_id' => $bib->id]);
                event(new StartingNumberAssigned($registration, $newNumber));
            }
        } else {
            $bib = Bib::firstOrCreate(
                ['number' => $newNumber]
            );
            StartingNumber::create([
                'registration_id' => $registration->id,
                'bib_id' => $bib->id,
            ]);
            event(new StartingNumberAssigned($registration, $newNumber));
        }
    }
}
