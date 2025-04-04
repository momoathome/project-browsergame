<?php

namespace Orion\Modules\Spacecraft\Services;

use Illuminate\Support\Collection;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;

readonly class SpacecraftService
{

    public function __construct(
        private readonly SpacecraftRepository $spacecraftRepository,
        private readonly ActionQueueService $queueService,
    ) {
    }

    public function findSpacecraftById(int $id, int $userId)
    {
        return $this->spacecraftRepository->findSpacecraftById($id, $userId);
    }

    public function getAllSpacecraftsByUserId(int $userId): Collection
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserId($userId);
    }

    public function getAllSpacecraftsByUserIdWithDetails(int $userId, ?Collection $filteredNames = null): Collection
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetails($userId, $filteredNames);
    }

    public function getAllSpacecraftsByUserIdWithDetailsAndResources(int $userId): Collection
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetailsAndResources($userId);
    }

    public function getAllSpacecraftsByUserIdWithQueueInformation(int $userId): Collection
    {
        return $this->addQueueInformationToSpacecrafts($userId);
    }

    public function addQueueInformationToSpacecrafts(int $userId): Collection
    {
        $spacecrafts = $this->getAllSpacecraftsByUserIdWithDetailsAndResources($userId);
        $spacecraftQueues = $this->queueService->getInProgressQueuesFromUserByType($userId, QueueActionType::ACTION_TYPE_PRODUCE);

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

    public function filterSpacecrafts($spaceCrafts): Collection
    {
        $collection = $spaceCrafts instanceof Collection
            ? $spaceCrafts
            : collect($spaceCrafts);

        return $collection->filter(function ($count) {
            return $count > 0;
        });
    }

    public function lockSpacecrafts($user, Collection $filteredSpacecrafts): bool
    {
        return $this->spacecraftRepository->lockSpacecrafts($user, $filteredSpacecrafts);
    }

    public function freeSpacecrafts($user, Collection $filteredSpacecrafts): bool
    {
        return $this->spacecraftRepository->freeSpacecrafts($user, $filteredSpacecrafts);
    }

    public function updateSpacecraftsCount($userId, Collection $spacecrafts): void
    {
        $this->spacecraftRepository->updateSpacecraftsCount($userId, $spacecrafts);
    }

    /**
     * Formatiert Raumschiffe für die Anzeige
     * 
     * @param int $userId Die ID des Benutzers
     * @return array Formatierte Raumschiffdaten
     */
    public function formatSpacecraftsForDisplay(int $userId): array
    {
        $spacecrafts = $this->getAllSpacecraftsByUserIdWithQueueInformation($userId);
        $formattedSpacecrafts = [];

        foreach ($spacecrafts as $spacecraft) {
            // Basisinformationen
            $formattedSpacecraft = [
                'id' => $spacecraft->id,
                'image' => $spacecraft->details->image,
                'name' => $spacecraft->details->name,
                'description' => $spacecraft->details->description,
                'type' => $spacecraft->details->type,
                'combat' => $spacecraft->combat,
                'count' => $spacecraft->count,
                'locked_count' => $spacecraft->locked_count,
                'cargo' => $spacecraft->cargo,
                'speed' => $spacecraft->speed,
                'operation_speed' => $spacecraft->operation_speed,
                'build_time' => $spacecraft->build_time,
                'crew_limit' => $spacecraft->crew_limit,
                'unlocked' => $spacecraft->unlocked,
                'research_cost' => $spacecraft->research_cost,
                'is_producing' => $spacecraft->is_producing ?? false,
                'resources' => []
            ];

            // Füge Endzeit und aktuell produzierende Anzahl hinzu, wenn produzierend
            if ($spacecraft->is_producing ?? false) {
                $formattedSpacecraft['end_time'] = $spacecraft->end_time;
                $formattedSpacecraft['currently_producing'] = $spacecraft->currently_producing;
            }

            // Ressourcen formatieren
            foreach ($spacecraft->resources as $resource) {
                $formattedSpacecraft['resources'][] = [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'image' => $resource->image,
                    'amount' => $resource->pivot->amount
                ];
            }

            $formattedSpacecrafts[] = $formattedSpacecraft;
        }

        return $formattedSpacecrafts;
    }

    public function formatSpacecraftsSimple(int $userId): array 
    {
        $spacecrafts = $this->getAllSpacecraftsByUserIdWithDetails($userId);
        $formattedSpacecrafts = [];

        foreach ($spacecrafts as $spacecraft) {
            $formattedSpacecrafts[] = [
                'id' => $spacecraft->id,
                'name' => $spacecraft->details->name,
                'image' => $spacecraft->details->image,
                'type' => $spacecraft->details->type,
                'combat' => $spacecraft->combat,
                'count' => $spacecraft->count,
                'locked_count' => $spacecraft->locked_count,
                'cargo' => $spacecraft->cargo,
                'speed' => $spacecraft->speed,
                'operation_speed' => $spacecraft->operation_speed,
                'unlocked' => $spacecraft->unlocked,
            ];
        }

        return $formattedSpacecrafts;
    }
}
