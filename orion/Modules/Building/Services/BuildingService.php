<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Building\Repositories\BuildingRepository;
use Orion\Modules\Building\Services\BuildingCostCalculator;

class BuildingService
{
    public function __construct(
        private readonly BuildingRepository $buildingRepository,
        private readonly BuildingCostCalculator $buildingCostCalculator,
        private readonly QueueService $queueService,
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
        $buildingQueues = $this->queueService->getInProgressQueuesFromUserByType($userId, ActionQueue::ACTION_TYPE_BUILDING);

        return $buildings->map(function ($building) use ($buildingQueues) {
            $isUpgrading = isset($buildingQueues[$building->id]);
            $building->is_upgrading = $isUpgrading;
            $building->next_level_costs = $this->buildingCostCalculator->calculateUpgradeCost($building);

            if ($isUpgrading) {
                $building->end_time = $buildingQueues[$building->id]->end_time;
            }

            return $building;
        });
    }
}
