<?php

namespace Orion\Modules\User\Services;

use Illuminate\Support\Collection;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\User\Repositories\UserAttributeRepository;


class UserAttributeService
{
    public function __construct(
        private readonly UserAttributeRepository $userAttributeRepository,
        private readonly SpacecraftService $spacecraftService
    ) {
    }
    public function getAllUserAttributesByUserId($userId): Collection
    {
        // Holen der Attribute
        $userAttributes = $this->userAttributeRepository->getAllUserAttributesByUserId($userId);
    
        // Debug-Ausgabe hinzufügen, um die ursprünglichen Werte zu sehen
        \Log::debug("Original user attributes", [
            'user_id' => $userId,
            'attributes' => $userAttributes->toArray()
        ]);
    
        // Raumschiffe für TOTAL_UNITS berechnen
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserId($userId);
    
        // Berechnung der Crew-Kapazität
        $totalCrewLimit = $spacecrafts->sum(function ($spacecraft) {
            $totalCount = $spacecraft->count;
            return $totalCount > 0 ? $spacecraft->crew_limit * $totalCount : 0;
        });
    
        // Nur TOTAL_UNITS aktualisieren, keine anderen Attribute ändern
        $totalUnitsAttribute = $userAttributes->where('attribute_name', UserAttributeType::TOTAL_UNITS->value)->first();
        if ($totalUnitsAttribute) {
            $totalUnitsAttribute->attribute_value = $totalCrewLimit;
        }
    
        // Debug-Ausgabe für die endgültigen Werte
        \Log::debug("Final user attributes", [
            'user_id' => $userId,
            'attributes' => $userAttributes->toArray()
        ]);
    
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
