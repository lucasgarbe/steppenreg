<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\PaymentStatus;
use App\Models\Registration;

class PaymentService
{
    /** Confirmation channels. */
    public const VIA_MANUAL = 'manual';

    public const VIA_BANK_IMPORT = 'bank_import';

    /**
     * Mark a registration as paid.
     *
     * @param  self::VIA_*  $via
     */
    public function markPaid(Registration $registration, string $via = self::VIA_MANUAL): void
    {
        PaymentStatus::updateOrCreate(
            ['registration_id' => $registration->id],
            ['paid' => true, 'paid_via' => $via, 'paid_at' => now()],
        );
    }

    /**
     * Mark a registration as not paid.
     */
    public function markUnpaid(Registration $registration): void
    {
        PaymentStatus::updateOrCreate(
            ['registration_id' => $registration->id],
            ['paid' => false, 'paid_via' => null, 'paid_at' => null],
        );
    }
}
