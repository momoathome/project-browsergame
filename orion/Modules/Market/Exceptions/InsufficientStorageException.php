<?php

namespace Orion\Modules\Market\Exceptions;

class InsufficientStorageException extends \Exception
{
    public function __construct($message = "Not enough storage space available", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
