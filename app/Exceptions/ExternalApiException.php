<?php

namespace App\Exceptions;

use Exception;

class ExternalApiException extends Exception
{
    public function __construct(
        string $message = 'Error communicating with the external API.',
        int $code = 0,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
