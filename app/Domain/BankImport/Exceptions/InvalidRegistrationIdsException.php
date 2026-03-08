<?php

namespace App\Domain\BankImport\Exceptions;

use RuntimeException;

class InvalidRegistrationIdsException extends RuntimeException
{
    /**
     * @param  array<int>  $invalidIds
     */
    public function __construct(array $invalidIds)
    {
        $ids = implode(', ', $invalidIds);
        parent::__construct("The following registration IDs are invalid or do not exist: {$ids}");
    }
}
