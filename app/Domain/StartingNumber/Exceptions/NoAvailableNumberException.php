<?php

namespace App\Domain\StartingNumber\Exceptions;

use RuntimeException;

class NoAvailableNumberException extends RuntimeException
{
    public function __construct(int $trackId)
    {
        parent::__construct("No available starting numbers for track {$trackId}");
    }
}
