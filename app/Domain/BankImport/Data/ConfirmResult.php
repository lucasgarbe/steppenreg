<?php

namespace App\Domain\BankImport\Data;

/**
 * Value object returned by ConfirmPaymentsAction::execute().
 */
readonly class ConfirmResult
{
    /**
     * @param  array<int>  $already_paid_ids  registrations that were paid before; their transfers stay unresolved
     */
    public function __construct(
        public int $newly_confirmed,
        public int $already_paid,
        public array $already_paid_ids = [],
    ) {}
}
