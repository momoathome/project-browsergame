<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Building\Repositories\BuildingRepository;
use Orion\Modules\Building\Services\BuildingCostCalculator;
use Orion\Modules\Building\Services\BuildingEffectCalculator;

class BuildingService
{
    public function __construct(
        private readonly BuildingRepository $buildingRepository,
        private readonly BuildingCostCalculator $buildingCostCalculator,
        private readonly QueueService $queueService,
        private readonly BuildingEffectCalculator $buildingEffectCalculator
    ) {
    }
    
    public function getAllBuildingsByUserId(int $userId)
    {
        return $this->buildingRepository->getAllBuildingsByUserId($userId);
    }

    public function getAllBuildingsByUserIdWithDetailsAndResources(int $userId)
    {
        return $this->buildingRepository->getAllBuildingsByUserIdWithDetailsAndResources($userId);
    }

    public function getAllBuildingsByUserIdWithQueueInformation(int $userId)
    {
        return $this->addQueueInformationToBuildings($userId);
    }

    public function getOneBuildingByUserId(int $buildingId, int $userId)
    {
        return $this->buildingRepository->getOneBuildingByUserId($buildingId, $userId);
    }

    public function addQueueInformationToBuildings(int $userId)
    {
        $buildings = $this->getAllBuildingsByUserIdWithDetailsAndResources($userId);
        $buildingQueues = $this->queueService->getInProgressQueuesFromUserByType($userId, QueueActionType::ACTION_TYPE_BUILDING);

        return $buildings->map(function ($building) use ($buildingQueues) {
            $isUpgrading = isset($buildingQueues[$building->id]);
            $building->is_upgrading = $isUpgrading;

            if ($isUpgrading) {
                $building->end_time = $buildingQueues[$building->id]->end_time;
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
            foreach ($building->resources as $resource) {
                $formattedBuilding['resources'][] = [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'image' => $resource->image,
                    'amount' => $resource->pivot->amount
                ];
            }

            // Effektinformationen hinzufügen
            $currentEffects = $this->buildingEffectCalculator->getEffectPreview($building);
            $nextLevelEffects = $this->buildingEffectCalculator->getEffectPreview($building, true);

            $formattedBuilding['current_effects'] = $currentEffects;
            $formattedBuilding['next_level_effects'] = $nextLevelEffects;

            $formattedBuildings[] = $formattedBuilding;
        }

        return $formattedBuildings;
    }
}
