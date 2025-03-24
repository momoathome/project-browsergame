<?php

namespace Orion\Modules\User\Services;

use Orion\Modules\User\Repositories\UserAttributeRepository;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\User\Enums\UserAttributeType;


class UserAttributeService
{
    public function __construct(
        private readonly UserAttributeRepository $userAttributeRepository,
        private readonly SpacecraftService $spacecraftService
    ) {
    }
    public function getAllUserAttributesByUserId($userId)
    {
        $userAttributes = $this->userAttributeRepository->getAllUserAttributesByUserId($userId);

        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserId($userId);

        // fill total units attribute
        $totalCrewLimit = $spacecrafts->sum(function ($spacecraft) {
            $totalCount = $spacecraft->count;
            return $totalCount > 0 ? $spacecraft->crew_limit * $totalCount : 0;
        });

        $totalUnitsAttribute = $userAttributes->where('attribute_name', 'total_units')->first();
        if ($totalUnitsAttribute) {
            $totalUnitsAttribute->attribute_value = $totalCrewLimit;
        }

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
