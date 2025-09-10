<?php

namespace Orion\Modules\Spacecraft\Exceptions;

class InsufficientCrewCapacityException extends \Exception
{
    public function __construct($message = "Not enough Crew Capacity", $code = 0)
    {
        parent::__construct($message, $code);
    }
}
