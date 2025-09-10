<?php

namespace Orion\Modules\Building\Services;

use Illuminate\Support\Collection;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Building\Repositories\BuildingRepository;
use Orion\Modules\Building\Services\BuildingProgressionService;
use Orion\Modules\Resource\Services\ResourceService;

class BuildingService
{
    public function __construct(
        private readonly BuildingRepository $buildingRepository,
        private readonly ActionQueueService $queueService,
        private readonly BuildingProgressionService $buildingProgressionService,
        private readonly ResourceService $resourceService
    ) {
    }
    
    public function getAllBuildingsByUserId(int $userId): Collection
    {
        return $this->buildingRepository->getAllBuildingsByUserId($userId);
    }

    public function getAllBuildingsByUserIdWithDetailsAndResources(int $userId): Collection
    {
        return $this->buildingRepository->getAllBuildingsByUserIdWithDetailsAndResources($userId);
    }

    public function getAllBuildingsByUserIdWithQueueInformation(int $userId): Collection
    {
        return $this->addQueueInformationToBuildings($userId);
    }

    public function getOneBuildingByUserId(int $buildingId, int $userId)
    {
        return $this->buildingRepository->getOneBuildingByUserId($buildingId, $userId);
    }

    public function addQueueInformationToBuildings(int $userId): Collection
    {
        $buildings = $this->getAllBuildingsByUserIdWithDetailsAndResources($userId);
        $buildingQueues = $this->queueService->getInProgressQueuesFromUserByType($userId, QueueActionType::ACTION_TYPE_BUILDING);
    
        return $buildings->map(function ($building) use ($buildingQueues) {
            $queueInfo = $buildingQueues->get($building->id);
            $building->is_upgrading = $queueInfo !== null;
            
            if ($building->is_upgrading) {
                $building->end_time = $queueInfo->end_time;
            }
    
            return $building;
        });
    }

    /**
     * Formatiert Gebäude für die Anzeige und fügt Effektinformationen hinzu
     * 
     * @param int $userId Die ID des Benutzers
     * @return array Formatierte Gebäudedaten mit Effektinformationen
     */
    public function formatBuildingsForDisplay(int $userId): array
    {
        $buildings = $this->getAllBuildingsByUserIdWithQueueInformation($userId);
        $formattedBuildings = [];

        foreach ($buildings as $building) {
            $queuedUpgrades = $this->queueService->getQueuedUpgradesCount($userId, $building->id, QueueActionType::ACTION_TYPE_BUILDING);
            $nextUpgradeLevel = $building->level + $queuedUpgrades + 1;
            // Hole die Ressourcen für das nächste Level
            $nextLevelResources = $this->buildingProgressionService->calculateUpgradeCost($building, $nextUpgradeLevel);
            $allResources = $this->resourceService->getAllResources()->keyBy('id');

            // Basisinformationen
            $formattedBuilding = [
                'id' => $building->id,
                'name' => $building->details->name,
                'description' => $building->details->description,
                'image' => $building->details->image,
                'level' => $building->level,
                'build_time' => $building->build_time,
                'is_upgrading' => $building->is_upgrading,
                'resources' => []
            ];

            // Füge Endzeit hinzu, wenn upgrading
            if ($building->is_upgrading) {
                $formattedBuilding['end_time'] = $building->end_time;
            }

            // Ressourcen formatieren
            foreach ($nextLevelResources as $resource) {
                $image = $allResources->get($resource['id'])?->image ?? null;
                $formattedBuilding['resources'][] = [
                    'id' => $resource['id'],
                    'name' => $resource['name'],
                    'image' => $image,
                    'amount' => $resource['amount']
                ];
            }

            // Effektinformationen hinzufügen
            $formattedBuilding['effect'] = [
                'current' => $this->buildingProgressionService->getEffectPreview($building),
                'next_level' => $this->buildingProgressionService->getEffectPreview($building, true)
            ];

            $formattedBuildings[] = $formattedBuilding;
        }

        return $formattedBuildings;
    }
}
