<?php

namespace App\Exceptions;

use Exception;

class InvalidExternalDataException extends Exception
{
    public function __construct(
        string $message = 'Los datos externos no son válidos.',
        int $code = 0,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
