<?php

namespace Orion\Modules\Building\Services;

use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Building\Models\BuildingResourceCost;

class SetupInitialBuildings
{
    public function __construct(
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

    public function reset(int $userId): void
    {
        // Alle Gebäude-IDs des Users holen
        $buildingIds = Building::where('user_id', $userId)->pluck('id');
    
        // Alle zugehörigen BuildingResourceCost-Einträge löschen
        BuildingResourceCost::whereIn('building_id', $buildingIds)->delete();
    
        // Dann die Gebäude selbst löschen
        Building::where('user_id', $userId)->delete();
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
            $baseValue = $effectConfig['base_value'] ?? 0;
            
            foreach ($effectAttributeNames as $attributeNameStr) {
                // String zu UserAttributeType Enum konvertieren
                $attributeName = UserAttributeType::tryFrom($attributeNameStr);
                
                // Falls die Umwandlung fehlschlägt, überspringen
                if ($attributeName === null) {
                    Log::warning("Ungültiger Attributtyp: {$attributeNameStr}", [
                        'user_id' => $userId,
                        'building_id' => $building->id
                    ]);
                    continue;
                }
                
                $this->userAttributeService->updateUserAttribute(
                    $userId,
                    $attributeName,
                    $baseValue,
                    false,  // nicht multiplizieren
                    true    // Wert komplett ersetzen
                );
            }
        }
    }
}
