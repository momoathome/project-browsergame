<?php

namespace Orion\Modules\Spacecraft\Exceptions;

class InsufficientCrewCapacityException extends \Exception
{
    public function __construct($message = "Nicht genug Crew-Kapazität", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
