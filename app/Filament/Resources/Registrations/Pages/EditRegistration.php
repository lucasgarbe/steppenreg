<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Domain\StartingNumber\Events\StartingNumberAssigned;
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

        $newNumber = $this->data['starting_number_manual'] ?? null;
        $registration = $this->getRecord();
        $service = app(StartingNumberService::class);

        if (blank($newNumber)) {
            $service->clearNumber($registration);

            return;
        }

        $newNumber = (int) $newNumber;
        $existing = $registration->startingNumber;

        if ($existing) {
            if ($existing->number !== $newNumber) {
                $existing->update(['number' => $newNumber]);
                event(new StartingNumberAssigned($registration, $newNumber));
            }
        } else {
            StartingNumber::create([
                'registration_id' => $registration->id,
                'number' => $newNumber,
            ]);
            event(new StartingNumberAssigned($registration, $newNumber));
        }
    }
}
