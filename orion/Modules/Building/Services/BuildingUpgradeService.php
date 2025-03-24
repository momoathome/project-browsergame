<?php

namespace Orion\Modules\Building\Services;

use Illuminate\Support\Facades\DB;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Building\Services\BuildingCostCalculator;

class BuildingUpgradeService
{
    public function __construct(
        private readonly BuildingService $buildingService,
        private readonly BuildingCostCalculator $costCalculator,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService,
        private readonly QueueService $queueService,
        private readonly ResourceService $resourceService
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
            QueueActionType::ACTION_TYPE_BUILDING,
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
        $buildingType = BuildingType::tryFrom($building->details->name);

        // Hier die Logik für Effektverbesserung je nach Gebäudetyp
        // Dies ist nur ein einfaches Beispiel
        switch ($buildingType) {
            case BuildingType::SHIPYARD:
                return 1.1 + ($building->level - 1) * 0.05;
            case BuildingType::HANGAR:
                return 10 + ($building->level - 1) * 10;
            case BuildingType::WAREHOUSE:
                return 5 + ($building->level - 1) * 2;
            case BuildingType::LABORATORY:
                return 3 + ($building->level - 1) * 1;
            case BuildingType::SCANNER:
                return 2 + ($building->level - 1) * 0.5;
            case BuildingType::SHIELD:
                return 1 + ($building->level - 1) * 0.2;
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
    
        $resources = $this->resourceService->getResourceIdMapping();
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

            $buildingType = BuildingType::tryFrom($building->details->name);

            if ($buildingType) {
                $this->updateUserAttributesForBuilding($userId, $building, $buildingType);
            }

            return true;
        });
    }

    private function updateUserAttributesForBuilding(int $userId, Building $building, BuildingType $buildingType): void
    {
        $effectAttributes = $buildingType->getEffectAttributes();
        
        foreach ($effectAttributes as $attributeName => $config) {
            $value = $building->effect_value + ($config['modifier'] ?? 0);
            $multiply = $config['multiply'] ?? false;
            $replace = $config['replace'] ?? false;
            
            $this->userAttributeService->updateUserAttribute(
                $userId,
                $attributeName,
                $value,
                $multiply,
                $replace
            );
        }
    }
}
