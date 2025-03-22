<?php

namespace Orion\Modules\Building\Services;

use Illuminate\Support\Facades\DB;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Building\Services\BuildingCostCalculator;
use Orion\Modules\Building\Services\BuildingService;

class BuildingUpgradeService
{
    public function __construct(
        private readonly BuildingService $buildingService,
        private readonly BuildingCostCalculator $costCalculator,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService,
        private readonly QueueService $queueService,
    ) {
    }

    /**
     * Startet ein Gebäude-Upgrade, wenn alle Voraussetzungen erfüllt sind
     * 
     * @param int $userId
     * @param Building $building
     * @throws \Exception wenn Ressourcen nicht ausreichen
     */
    public function startBuildingUpgrade(int $userId, Building $building): void
    {
        $currentCosts = $this->getBuildingUpgradeCosts($building);

        // Prüfe, ob der User genügend Ressourcen hat
        $this->validateUserHasEnoughResources($userId, $currentCosts);

        // Führe das Upgrade in einer Transaktion durch
        DB::transaction(function () use ($userId, $building, $currentCosts) {
            // Ressourcen abziehen
            $this->decrementResourcesFromUser($userId, $currentCosts);

            // Upgrade zur Queue hinzufügen
            $this->addBuildingUpgradeToQueue($userId, $building);
        });
    }

    private function getBuildingUpgradeCosts(Building $building): array
    {
        return $building->resources()->get()->map(function ($resource) {
            // Statt Objekt ein einfaches Array zurückgeben
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'amount' => $resource->pivot->amount
            ];
        })->keyBy('id')->toArray();
    }
    
    private function validateUserHasEnoughResources(int $userId, array $requiredResources): void
    {
        $userResources = collect($this->userResourceService->getAllUserResourcesByUserId($userId))
            ->keyBy('resource_id');
        
        foreach ($requiredResources as $resourceId => $resourceCost) {
            $userResource = $userResources->get($resourceId);
            $requiredAmount = $resourceCost['amount'];
            
            if (!$userResource || $userResource->amount < $requiredAmount) {
                $resourceName = $resourceCost['name'] ?? 'Resource #' . $resourceId;
                throw new \Exception("Not enough {$resourceName}");
            }
        }
    }

    private function decrementResourcesFromUser(int $userId, array $requiredResources): void
    {
        foreach ($requiredResources as $resourceId => $resource) {
            $this->userResourceService->subtractResourceAmount($userId, $resourceId, $resource['amount']);
        }
    }

    private function addBuildingUpgradeToQueue(int $userId, Building $building): void
    {
        $this->queueService->addToQueue(
            $userId,
            ActionQueue::ACTION_TYPE_BUILDING,
            $building->id,
            $building->build_time,
            [
                'building_name' => $building->details->name,
                'current_level' => $building->level,
                'next_level' => $building->level + 1,
            ]
        );
    }

    public function calculateUpgradeCosts(Building $building): array
    {
        return $this->costCalculator->calculateUpgradeCost($building) ?? [];
    }

    public function upgradeBuilding(Building $building)
    {
        $costs = $this->calculateUpgradeCosts($building);

        return DB::transaction(function () use ($building, $costs) {
            // Gebäude aktualisieren
            $building->level += 1;
            $building->effect_value = $this->calculateNewEffectValue($building);
            $building->build_time = $this->calculateBuildTime($building);
            $building->save();

            // Neue Kosten für das nächste Level speichern
            $this->updateBuildingCosts($building, $costs);

            return $building;
        });
    }

    private function calculateNewEffectValue(Building $building)
    {
        // Hier die Logik für Effektverbesserung je nach Gebäudetyp
        // Dies ist nur ein einfaches Beispiel
        switch ($building->details->name) {
            case 'Shipyard':
                return 1.1 + ($building->level - 1) * 0.05;
            case 'Hangar':
                return 10 + ($building->level - 1) * 10;
            // Weitere Gebäude...
            default:
                return $building->effect_value * 1.1;
        }
    }

    private function calculateBuildTime(Building $building)
    {
        // Bauzeit je nach Level erhöhen
        return floor(60 * pow(1.2, $building->level - 1));
    }

    private function updateBuildingCosts(Building $building, $costs)
    {
        // Bestehende Verknüpfungen löschen
        $building->resources()->detach();

        $resources = Resource::pluck('id', 'name')->toArray();
        $resourceData = [];

        foreach ($costs as $cost) {
            if (isset($resources[$cost['resource_name']])) {
                $resourceId = $resources[$cost['resource_name']];
                $resourceData[$resourceId] = ['amount' => $cost['amount']];
            }
        }

        // Neue Verknüpfungen mit Pivot-Daten erstellen
        $building->resources()->attach($resourceData);
    }

    public function completeUpgrade($buildingId, $userId)
    {
        $building = $this->buildingService->getOneBuildingByUserId($buildingId, $userId);

        if (!$building) {
            return false;
        }

        return DB::transaction(function () use ($building, $userId) {
            // Upgrade durchführen
            $this->upgradeBuilding($building);

            // User-Attribute basierend auf dem Gebäudetyp aktualisieren
            $buildingEffects = [
                'Shipyard' => ['production_speed' => $building->effect_value - 1, 'replace' => true],
                'Hangar' => ['crew_limit' => $building->effect_value],
                'Warehouse' => ['storage' => $building->effect_value, 'multiply' => true],
                'Laboratory' => ['research_points' => $building->effect_value],
                'Scanner' => ['scan_range' => $building->effect_value],
                'Shield' => ['base_defense' => $building->effect_value - 1, 'replace' => true],
            ];

            if (isset($buildingEffects[$building->details->name])) {
                foreach ($buildingEffects[$building->details->name] as $attributeName => $value) {
                    if (!is_array($value)) {
                        $this->userAttributeService->updateUserAttribute(
                            $userId,
                            $attributeName,
                            $value,
                            $buildingEffects[$building->details->name]['multiply'] ?? false,
                            $buildingEffects[$building->details->name]['replace'] ?? false
                        );
                    }
                }
            }

            return true;
        });
    }
}
