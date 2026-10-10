<?php

namespace App\Domain\BankImport\Data;

/**
 * Value object returned by ConfirmPaymentsAction::execute().
 */
readonly class ConfirmResult
{
    /**
     * @param  int  $newly_confirmed  registrations marked paid by this call
     * @param  int  $already_paid  transfers not booked because their registration was paid already
     * @param  int  $linked  transfers booked for registrations that were paid without a booked transfer
     * @param  list<string>  $booked_fingerprints  fingerprints of transfers recorded as a registration's payment
     */
    public function __construct(
        public int $newly_confirmed,
        public int $already_paid,
        public int $linked = 0,
        public array $booked_fingerprints = [],
    ) {}
}
