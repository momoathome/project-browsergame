<?php

namespace Orion\Modules\User\Services;

use Illuminate\Support\Collection;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Handlers\UserAttributeHandler;
use Orion\Modules\User\Repositories\UserAttributeRepository;

class UserAttributeService
{
    public function __construct(
        private readonly UserAttributeRepository $userAttributeRepository,
        private readonly UserAttributeHandler $userAttributeHandler
    ) {
    }

    public function getInfluenceOfAllUsers(): Collection
    {
        return $this->userAttributeRepository->getInfluenceOfAllUsers();
    }
    
    public function getAllUserAttributesByUserId($userId): Collection
    {
        // Holen der Attribute
        $userAttributes = $this->userAttributeRepository->getAllUserAttributesByUserId($userId);
    
        // Verwende den Handler, um TOTAL_UNITS zu berechnen und zu aktualisieren
        $userAttributes = $this->userAttributeHandler->updateTotalUnitsAttribute($userId, $userAttributes);
    
        return $userAttributes;
    }

    public function getSpecificUserAttribute($userId, UserAttributeType $attributeName)
    {
        return $this->userAttributeRepository->getSpecificUserAttribute($userId, $attributeName);
    }

    public function updateUserAttribute($userId, $attributeName, $value, $multiply = false, $replace = false)
    {
        $userAttribute = $this->getSpecificUserAttribute($userId, $attributeName);

        if ($userAttribute) {
            if ($replace) {
                // Komplett ersetzen
                $userAttribute->attribute_value = $value;
            } else if ($multiply) {
                // Wert als Multiplikator verwenden
                $userAttribute->attribute_value = round($userAttribute->attribute_value * $value);
            } else {
                // Wert addieren (Standard)
                $userAttribute->attribute_value += $value;
            }
            
            // Sicherstellen, dass der Wert nicht negativ wird
            $userAttribute->attribute_value = max(0, $userAttribute->attribute_value);
            $userAttribute->save();
            
            return $userAttribute;
        }
        
        return null;
    }

    public function addAttributeAmount(int $userId, UserAttributeType $attributeName, int $amount)
    {
        $userAttribute = $this->getSpecificUserAttribute($userId, $attributeName);
        $userAttribute->attribute_value += $amount;
        $userAttribute->save();
    }

    public function subtractAttributeAmount(int $userId, UserAttributeType $attributeName, int $amount)
    {
        $userAttribute = $this->getSpecificUserAttribute($userId, $attributeName);
        $userAttribute->attribute_value -= $amount;
        $userAttribute->save();
    }
}
