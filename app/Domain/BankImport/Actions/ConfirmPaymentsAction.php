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
     * Confirm bank transfers as registration payments.
     *
     * A registration has at most one booked transfer. Transfers are handled in order:
     * - the transfer is already booked for the registration: nothing changes (idempotent)
     * - the registration has another booked transfer: not booked, it is a duplicate
     * - the registration is paid without a booked transfer: the transfer is linked
     *   as its payment, the payment provenance stays untouched
     * - otherwise the registration is marked paid and the transfer is booked
     *
     * @param  list<array{registration_id: int, fingerprint?: string|null}>  $transfers
     *
     * @throws InvalidRegistrationIdsException when any ID does not exist or is soft-deleted
     */
    public function execute(array $transfers): ConfirmResult
    {
        if (empty($transfers)) {
            return new ConfirmResult(newly_confirmed: 0, already_paid: 0);
        }

        $registrationIds = array_values(array_unique(array_column($transfers, 'registration_id')));
        $registrations = Registration::whereIn('id', $registrationIds)->get()->keyBy('id');

        $invalidIds = array_values(array_diff($registrationIds, $registrations->keys()->all()));

        if (! empty($invalidIds)) {
            throw new InvalidRegistrationIdsException($invalidIds);
        }

        $bookedByRegistration = BankTransferConfirmation::query()
            ->whereIn('registration_id', $registrationIds)
            ->get()
            ->groupBy('registration_id')
            ->map(fn ($confirmations) => $confirmations->pluck('fingerprint')->all())
            ->all();

        return DB::transaction(function () use ($transfers, $registrations, $bookedByRegistration) {
            $paymentService = app(PaymentService::class);
            $paidIds = $registrations->filter(fn (Registration $registration) => $registration->is_paid)->keys()->all();

            $newlyConfirmed = 0;
            $alreadyPaid = 0;
            $linked = 0;
            $bookedFingerprints = [];

            foreach ($transfers as $transfer) {
                $registration = $registrations[$transfer['registration_id']];
                $fingerprint = ($transfer['fingerprint'] ?? '') !== '' ? $transfer['fingerprint'] : null;
                $booked = $bookedByRegistration[$registration->id] ?? [];

                if ($fingerprint !== null && in_array($fingerprint, $booked, true)) {
                    $bookedFingerprints[] = $fingerprint;

                    continue;
                }

                $isPaid = in_array($registration->id, $paidIds, true);

                if ($booked !== [] || ($isPaid && $fingerprint === null)) {
                    $alreadyPaid++;

                    continue;
                }

                if ($isPaid) {
                    $linked++;
                } else {
                    $paymentService->markPaid($registration, PaymentService::VIA_BANK_IMPORT);
                    $paidIds[] = $registration->id;
                    $newlyConfirmed++;
                }

                if ($fingerprint !== null) {
                    BankTransferConfirmation::create([
                        'registration_id' => $registration->id,
                        'fingerprint' => $fingerprint,
                    ]);
                    $bookedByRegistration[$registration->id][] = $fingerprint;
                    $bookedFingerprints[] = $fingerprint;
                }
            }

            return new ConfirmResult(
                newly_confirmed: $newlyConfirmed,
                already_paid: $alreadyPaid,
                linked: $linked,
                booked_fingerprints: $bookedFingerprints,
            );
        });
    }
}
