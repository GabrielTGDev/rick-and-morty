<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidExternalDataException extends Exception
{
    public function __construct(
        string $message = 'External data is not valid.',
        int $code = 0,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
