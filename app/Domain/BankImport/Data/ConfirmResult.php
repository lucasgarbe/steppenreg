<?php

namespace App\Domain\BankImport\Data;

/**
 * Value object returned by ConfirmPaymentsAction::execute().
 */
readonly class ConfirmResult
{
    public function __construct(
        public int $newly_confirmed,
        public int $already_payed,
    ) {}
}
