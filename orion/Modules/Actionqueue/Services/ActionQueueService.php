<?php

namespace Orion\Modules\Actionqueue\Services;

use App\Services\UserService;
use App\Events\GettingAttacked;
use App\Models\ActionQueueArchive;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Actionqueue\Dto\ActionQueueDTO;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use Orion\Modules\Actionqueue\Handlers\CombatHandler;
use Orion\Modules\Actionqueue\Handlers\AsteroidMiningHandler;
use Orion\Modules\Actionqueue\Handlers\BuildingUpgradeHandler;
use Orion\Modules\Actionqueue\Repositories\ActionQueueRepository;
use Orion\Modules\Actionqueue\Handlers\SpacecraftProductionHandler;
use Orion\Modules\ActionQueueArchive\Services\ActionQueueArchiveService;

class ActionQueueService
{
    public function __construct(
        private readonly ActionQueueRepository $actionqueueRepository,
        private readonly ActionQueueArchiveService $actionQueueArchiveService,
        private readonly UserService $userService
    ) {
    }

    public function getActionQueue(): Collection
    {
        return $this->actionqueueRepository->getActionQueue();
    }

    public function getUserQueue($userId): Collection
    {
        return $this->actionqueueRepository->getUserQueue($userId);
    }

    public function getInProgressQueuesByUser($userId): Collection
    {
        return $this->actionqueueRepository->getInProgressQueuesByUser($userId);
    }

    public function getQueuesFromUserByType($userId, $actionType): Collection
    {
        return $this->actionqueueRepository->getQueuesFromUserByType($userId, $actionType);
    }

    public function addToQueue($userId, $actionType, $targetId, $duration, $details, $status = QueueStatusType::STATUS_IN_PROGRESS)
    {
        $queueEntry = $this->actionqueueRepository->addToQueue(
            $userId,
            $actionType,
            $targetId,
            $duration,
            $details,
            $status,
        );

        // Wenn es sich um einen Kampf handelt, informiere den Verteidiger
        if ($actionType === QueueActionType::ACTION_TYPE_COMBAT) {
            $defender = $this->userService->find($targetId);
            $attacker = $this->userService->find($userId);

            if ($defender) {
                $attackDTO = ActionQueueDTO::fromModel($queueEntry, $attacker->name);
                event(new GettingAttacked($defender, $attackDTO));
            }
        }

        return $queueEntry;
    }

    public function getInProgressQueuesFromUserByType($userId, $actionType): Collection
    {
        return $this->actionqueueRepository->getInProgressQueuesFromUserByType($userId, $actionType);
    }

    public function getQueuedUpgrades($userId, $targetId, $actionType): Collection
    {
        return $this->actionqueueRepository->getQueuedUpgrades($userId, $targetId, $actionType);
    }

    public function processQueue(): int
    {
        $completedActions = $this->actionqueueRepository->processQueue() ?? [];

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }

        return count($completedActions);
    }

    public function processQueueForUser($userId): void
    {
        $completedActions = $this->actionqueueRepository->processQueueForUser($userId) ?? [];

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUserInstant($userId): void
    {
        // check for admin role
        $completedActions = $this->actionqueueRepository->processQueueForUserInstant($userId);

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function completeAction(ActionQueue $action): void
    {
        // Aktionstyp normalisieren - falls als String statt Enum
        $actionType = $action->action_type;

        if (is_string($actionType)) {
            // Versuche, den String in den entsprechenden Enum-Wert zu konvertieren
            try {
                $actionType = QueueActionType::from($action->action_type);
            } catch (\ValueError $e) {
                Log::error("Ungültiger Aktionstyp", [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'error' => $e->getMessage()
                ]);
                $action->status = QueueStatusType::STATUS_FAILED;
                $action->save();
                return;
            }
        }

        // Handlerklassen für verschiedene Aktionstypen
        $handlerClass = match ($actionType) {
            QueueActionType::ACTION_TYPE_BUILDING => BuildingUpgradeHandler::class,
            QueueActionType::ACTION_TYPE_PRODUCE => SpacecraftProductionHandler::class,
            QueueActionType::ACTION_TYPE_MINING => AsteroidMiningHandler::class,
            QueueActionType::ACTION_TYPE_COMBAT => CombatHandler::class,
            default => null
        };

        if (!$handlerClass) {
            Log::error("Kein Handler für Aktionstyp gefunden", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'normalized_type' => $actionType instanceof QueueActionType ? $actionType->value : $actionType
            ]);
            $action->status = QueueStatusType::STATUS_FAILED;
            $action->save();
            return;
        }

        $handler = App::make($handlerClass);

        try {
            $success = $handler->handle($action);

            if ($success) {
                $action->status = QueueStatusType::STATUS_COMPLETED;
            } else {
                $action->status = QueueStatusType::STATUS_FAILED;
            }

            $this->archiveCompletedQueue($action);

        } catch (\Exception $e) {
            Log::error("Fehler bei der Aktionsverarbeitung", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $action->status = QueueStatusType::STATUS_FAILED;
        }

        $action->save();

        if ($action->status === QueueStatusType::STATUS_COMPLETED) {
            // Unterschied nach ActionType
            if ($actionType === QueueActionType::ACTION_TYPE_PRODUCE) {
                // productionSlots aus details holen, fallback 1
                $productionSlots = $action->details['production_slots'] ?? 1;
                $this->promoteNextPendingProduce($action->user_id, $productionSlots);
            } elseif ($actionType === QueueActionType::ACTION_TYPE_BUILDING) {
                $this->promoteNextPendingBuilding($action->user_id, $action->target_id);
            } else {
                // Optional: für andere Typen nichts tun oder eigene Logik
            }
        }
    }

    private function archiveCompletedQueue(ActionQueue $action)
    {
        if (
            $action->status === QueueStatusType::STATUS_COMPLETED ||
            $action->status === QueueStatusType::STATUS_FAILED
        ) {
            // Kopiere in Archive-Tabelle und lösche original
            $this->actionQueueArchiveService->createArchiveEntry($action);
            $this->deleteFromQueue($action->id);
        }
    }

    /**
     * Fügt einen Spacecraft-Produktionsauftrag hinzu (Slots global pro User)
     */
    public function addSpacecraftToQueue(
        int $userId,
        int $targetId,
        int $duration,
        array $details,
        int $productionSlots = 1
    ): ActionQueue {
        $inProgressCount = $this->actionqueueRepository->countInProgressProduceByUser($userId);

        $status = $inProgressCount < $productionSlots
            ? QueueStatusType::STATUS_IN_PROGRESS
            : QueueStatusType::STATUS_PENDING;

        return $this->actionqueueRepository->create([
            'user_id' => $userId,
            'action_type' => QueueActionType::ACTION_TYPE_PRODUCE,
            'target_id' => $targetId,
            'start_time' => now(),
            'end_time' => now()->addSeconds($duration),
            'status' => $status,
            'details' => $details,
        ]);
    }

    /**
     * Fügt einen Gebäude-Upgrade-Auftrag hinzu (Slots pro Gebäude)
     */
    public function addBuildingToQueue(
        int $userId,
        int $targetId,
        int $duration,
        array $details,
        int $buildingSlots = 1
    ): ActionQueue {
        // Global: Wie viele Gebäude-Upgrades laufen gerade?
        $globalInProgress = $this->actionqueueRepository->countInProgressBuildingByUser($userId);
        // Pro Gebäude: Läuft für dieses Gebäude schon ein Upgrade?
        $buildingInProgress = $this->actionqueueRepository->countInProgressBuildingByUserAndTarget($userId, $targetId);

        $status = ($globalInProgress < $buildingSlots && $buildingInProgress === 0)
            ? QueueStatusType::STATUS_IN_PROGRESS
            : QueueStatusType::STATUS_PENDING;

        return $this->actionqueueRepository->create([
            'user_id' => $userId,
            'action_type' => QueueActionType::ACTION_TYPE_BUILDING,
            'target_id' => $targetId,
            'start_time' => now(),
            'end_time' => now()->addSeconds($duration),
            'status' => $status,
            'details' => $details,
        ]);
    }

    /**
     * Promotet das nächste Pending für Spacecrafts (global slots)
     */
    public function promoteNextPendingProduce(int $userId, int $productionSlots = 1): void
    {
        $inProgressCount = $this->actionqueueRepository->countInProgressProduceByUser($userId);

        if ($inProgressCount < $productionSlots) {
            $pending = $this->actionqueueRepository->getFirstPendingProduceByUser($userId);
            if ($pending) {
                $duration = $pending->details['duration'] ?? 60;
                $this->actionqueueRepository->update($pending, [
                    'status' => QueueStatusType::STATUS_IN_PROGRESS,
                    'start_time' => now(),
                    'end_time' => now()->addSeconds($duration),
                ]);
            }
        }
    }

    /**
     * Promotet das nächste Pending für Gebäude (pro Gebäude)
     */
    public function promoteNextPendingBuilding(int $userId, int $targetId, int $buildingSlots = 1): void
    {
        $globalInProgress = $this->actionqueueRepository->countInProgressBuildingByUser($userId);
        $buildingInProgress = $this->actionqueueRepository->countInProgressBuildingByUserAndTarget($userId, $targetId);

        if ($globalInProgress < $buildingSlots && $buildingInProgress === 0) {
            $pending = $this->actionqueueRepository->getFirstPendingBuildingByUserAndTarget($userId, $targetId);
            if ($pending) {
                $duration = $pending->details['duration'] ?? 60;
                $this->actionqueueRepository->update($pending, [
                    'status' => QueueStatusType::STATUS_IN_PROGRESS,
                    'start_time' => now(),
                    'end_time' => now()->addSeconds($duration),
                ]);
            }
        }
    }

    public function deleteFromQueue(int $id): bool
    {
        return $this->actionqueueRepository->delete($id);
    }

}
