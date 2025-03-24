<?php

namespace Orion\Modules\Market\Exceptions;

class InsufficientResourceException extends \Exception
{
    public function __construct($message = "Not enough resources available for this transaction", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
