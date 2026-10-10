<?php

namespace App\Domain\BankImport\Actions;

use App\Domain\BankImport\Data\ConfirmResult;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
use App\Domain\BankImport\Models\BankTransferConfirmation;
use App\Domain\Payment\Services\PaymentService;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentsAction
{
    /**
     * Confirm bank transfer payments: mark the given registrations as paid
     * and record transfer fingerprints in the confirmation ledger.
     *
     * A registration that is already paid is left untouched and its transfer
     * is not recorded, so a second transfer stays unresolved instead of
     * silently disappearing.
     *
     * @param  array<int>  $registrationIds
     * @param  array<int, string>  $fingerprintsByRegistrationId  client-computed transfer fingerprints keyed by registration id
     *
     * @throws InvalidRegistrationIdsException when any ID does not exist or is soft-deleted
     */
    public function execute(array $registrationIds, array $fingerprintsByRegistrationId = []): ConfirmResult
    {
        if (empty($registrationIds)) {
            return new ConfirmResult(newly_confirmed: 0, already_paid: 0);
        }

        $registrations = Registration::whereIn('id', $registrationIds)->get();

        $foundIds = $registrations->pluck('id')->all();
        $invalidIds = array_values(array_diff($registrationIds, $foundIds));

        if (! empty($invalidIds)) {
            throw new InvalidRegistrationIdsException($invalidIds);
        }

        [$alreadyPaid, $unpaid] = $registrations->partition(fn (Registration $registration) => $registration->is_paid);

        DB::transaction(function () use ($unpaid, $fingerprintsByRegistrationId) {
            $paymentService = app(PaymentService::class);

            foreach ($unpaid as $registration) {
                $paymentService->markPaid($registration, PaymentService::VIA_BANK_IMPORT);

                $fingerprint = $fingerprintsByRegistrationId[$registration->id] ?? null;
                if ($fingerprint !== null && $fingerprint !== '') {
                    BankTransferConfirmation::firstOrCreate([
                        'registration_id' => $registration->id,
                        'fingerprint' => $fingerprint,
                    ]);
                }
            }
        });

        return new ConfirmResult(
            newly_confirmed: $unpaid->count(),
            already_paid: $alreadyPaid->count(),
            already_paid_ids: $alreadyPaid->pluck('id')->values()->all(),
        );
    }
}
