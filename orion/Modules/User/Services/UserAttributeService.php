<?php

namespace Orion\Modules\User\Services;

use Illuminate\Support\Collection;
use Orion\Modules\User\Models\UserAttribute;
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

    public function getTotalAttributeValueByType(UserAttributeType $attributeType): int
    {
        return $this->userAttributeRepository->getTotalAttributeValueByType($attributeType);
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

    public function updateUserAttributesBatch(int $userId, array $updates, bool $multiply = false, bool $replace = false): void
    {
        $now = now();
        $attributes = [];

        foreach ($updates as $update) {
            $attributeName = $update['name'];
            $value = (float) $update['value'];

            // Bestimme den neuen Wert basierend auf der Operation
            if ($replace) {
                $newValue = $value;
            } elseif ($multiply) {
                $newValue = round($value);
            } else {
                $newValue = $value;
            }

            // Füge das Attribut zur Liste hinzu
            $attributes[] = [
                'user_id' => $userId,
                'attribute_name' => $attributeName,
                'attribute_value' => max(0, $newValue), // Sicherstellen, dass der Wert nicht negativ ist
                'updated_at' => $now,
            ];
        }

        // Verwende fillAndInsert, um die Datensätze zu speichern
        UserAttribute::fillAndInsert($attributes, ['user_id', 'attribute_name'], ['attribute_value', 'updated_at']);
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
