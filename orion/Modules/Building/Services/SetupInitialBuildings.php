<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Models\Building;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\Building\Enums\BuildingEffectType;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Building\Models\BuildingResourceCost;

class SetupInitialBuildings
{
    public function __construct(
        private readonly BuildingEffectCalculator $effectCalculator,
        private readonly UserAttributeService $userAttributeService
    ) {
    }

    public function create(int $userId)
    {
        $buildingsConfig = config('game.buildings.buildings');
        $resources = Resource::pluck('id', 'name')->toArray();
        $createdBuildings = [];

        foreach ($buildingsConfig as $buildingConfig) {
            $building = $this->createBuilding($userId, $buildingConfig);
            $createdBuildings[] = $building;

            foreach ($buildingConfig['costs'] as $cost) {
                BuildingResourceCost::create([
                    'building_id' => $building->id,
                    'resource_id' => $resources[$cost['resource_name']],
                    'amount' => $cost['amount'],
                ]);
            }
        }

        // Nachdem alle Gebäude erstellt wurden, aktualisiere die Benutzerattribute
        $this->applyInitialBuildingEffects($userId, $createdBuildings);
    }

    private function createBuilding(int $userId, array $buildingConfig)
    {
        return Building::create([
            'user_id' => $userId,
            'details_id' => $buildingConfig['details_id'],
            'level' => $buildingConfig['level'],
            'effect_value' => $buildingConfig['effect_value'],
            'build_time' => $buildingConfig['build_time'],
        ]);
    }

    /**
     * Wendet die Effekte aller Gebäude auf die Benutzerattribute an
     */
    private function applyInitialBuildingEffects(int $userId, array $buildings): void
    {
        foreach ($buildings as $building) {
            $buildingType = BuildingType::tryFrom($building->details->name);
            
            if (!$buildingType) {
                continue;
            }
    
            $effectAttributeNames = $buildingType->getEffectAttributes();
            $effectConfig = $buildingType->getEffectConfiguration();
            $effectType = $effectConfig['type'] ?? BuildingEffectType::MULTIPLICATIVE;
            
            foreach ($effectAttributeNames as $attributeName) {
                // Bestimme, ob multipliziert oder ersetzt werden soll basierend auf dem EffectType
                $multiply = false;
                $replace = false;
                
                switch ($effectType) {
                    case BuildingEffectType::ADDITIVE:
                        // Additive Effekte werden zum Wert hinzugefügt
                        break;
                        
                    case BuildingEffectType::MULTIPLICATIVE:
                        // Multiplikative Effekte verwenden den Wert als Faktor
                        $multiply = true;
                        break;
                        
                    case BuildingEffectType::EXPONENTIAL:
                    case BuildingEffectType::LOGARITHMIC:
                        // Diese komplexen Typen ersetzen den Wert komplett
                        $replace = true;
                        break;
                }
                
                $this->userAttributeService->updateUserAttribute(
                    $userId,
                    $attributeName,
                    $building->effect_value,
                    $multiply,
                    $replace
                );
            }
        }
    }
}
