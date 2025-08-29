<?php

namespace Orion\Modules\Actionqueue\Services;

use App\Services\UserService;
use App\Events\GettingAttacked;
use App\Models\ActionQueueArchive;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Dto\ActionQueueDTO;
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
    public function getUserQueue($userId): Collection
    {
        return $this->actionqueueRepository->getUserQueue($userId);
    }

    public function addToQueue($userId, $actionType, $targetId, $duration, $details)
    {
        $queueEntry = $this->actionqueueRepository->addToQueue(
            $userId,
            $actionType,
            $targetId,
            $duration,
            $details
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

    public function processQueue(): void
    {
        $completedActions = $this->actionqueueRepository->processQueue() ?? [];

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
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

    private function completeAction(ActionQueue $action): void
    {
        // Aktionstyp normalisieren - falls als String statt Enum
        $actionType = $action->action_type;

        if (is_string($actionType)) {
            // Versuche, den String in den entsprechenden Enum-Wert zu konvertieren
            try {
                $actionType = QueueActionType::from($action->action_type);
            } catch (\ValueError $e) {
                \Log::error("Ungültiger Aktionstyp", [
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
            \Log::error("Kein Handler für Aktionstyp gefunden", [
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
            \Log::error("Fehler bei der Aktionsverarbeitung", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $action->status = QueueStatusType::STATUS_FAILED;
        }

        $action->save();
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

    public function deleteFromQueue(int $id)
    {
        return $this->actionqueueRepository->deleteFromQueue($id);
    }

}
