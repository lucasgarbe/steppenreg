<?php

namespace App\Domain\BankImport\Actions;

use App\Domain\BankImport\Data\ConfirmResult;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
use App\Models\Registration;

class ConfirmPaymentsAction
{
    /**
     * Mark the given registration IDs as payed.
     *
     * @param  array<int>  $registrationIds
     *
     * @throws InvalidRegistrationIdsException when any ID does not exist or is soft-deleted
     */
    public function execute(array $registrationIds): ConfirmResult
    {
        if (empty($registrationIds)) {
            return new ConfirmResult(newly_confirmed: 0, already_payed: 0);
        }

        $registrations = Registration::whereIn('id', $registrationIds)->get();

        $foundIds = $registrations->pluck('id')->all();
        $invalidIds = array_values(array_diff($registrationIds, $foundIds));

        if (! empty($invalidIds)) {
            throw new InvalidRegistrationIdsException($invalidIds);
        }

        $alreadyPayed = $registrations->where('payed', true)->count();
        $toMark = $registrations->where('payed', false);

        foreach ($toMark as $registration) {
            $registration->update(['payed' => true]);
        }

        return new ConfirmResult(
            newly_confirmed: $toMark->count(),
            already_payed: $alreadyPayed,
        );
    }
}
