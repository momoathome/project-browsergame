<?php

namespace Orion\Modules\Spacecraft\Services;

use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Actionqueue\Models\ActionQueue;

readonly class SpacecraftService
{

    public function __construct(
        private SpacecraftRepository $spacecraftRepository,
        private QueueService $queueService
        
    ) {
    }

    public function getAllSpacecraftsByUserId(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserId($userId);
    }

    public function getAllSpacecraftsByUserIdWithDetails(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetails($userId);
    }
    
    public function getAllSpacecraftsByUserIdWithDetailsAndResources(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetailsAndResources($userId);
    }

    public function getAllSpacecraftsByUserIdWithQueueInformation(int $userId)
    {
        return $this->addQueueInformationToSpacecrafts($userId);
    }

    public function addQueueInformationToSpacecrafts(int $userId)
    {
        $spacecrafts = $this->getAllSpacecraftsByUserIdWithDetailsAndResources($userId);
        $spacecraftQueues = $this->queueService->getInProgressQueuesFromUserByType($userId, ActionQueue::ACTION_TYPE_PRODUCE);

        $spacecrafts = $spacecrafts->map(function ($spacecraft) use ($spacecraftQueues) {
            $isProducing = isset($spacecraftQueues[$spacecraft->id]);
            $spacecraft->is_producing = $isProducing;

            if ($isProducing) {
                $spacecraft->end_time = $spacecraftQueues[$spacecraft->id]->end_time;
                $spacecraft->currently_producing = $spacecraftQueues[$spacecraft->id]->details['quantity'] ?? 0;
            }

            return $spacecraft;
        });
        
        return $spacecrafts;
    }
}
