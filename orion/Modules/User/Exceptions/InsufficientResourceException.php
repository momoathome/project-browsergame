<?php

namespace Orion\Modules\Resource\Exceptions;

class InsufficientResourceException extends \Exception
{
    private string $resourceName;
    
    public function __construct(string $resourceName = "Ressource", $message = null, $code = 0, \Throwable $previous = null)
    {
        $this->resourceName = $resourceName;
        $message = $message ?? "Nicht genügend {$resourceName} vorhanden";
        parent::__construct($message, $code, $previous);
    }
    
    public function getResourceName(): string
    {
        return $this->resourceName;
    }
}
