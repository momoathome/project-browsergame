<?php

namespace Orion\Modules\Market\Exceptions;

class InsufficientCreditsException extends \Exception
{
    public function __construct($message = "User doesn't have enough credits for this transaction", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
