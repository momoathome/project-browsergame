<?php

namespace Orion\Modules\Actionqueue\Services;

use App\Services\UserService;
use InvalidArgumentException;
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

    public function claimQueueForUser($userId): Collection
    {
        return $this->actionqueueRepository->claimQueueForUser($userId);
    }


    public function completeAction(ActionQueue $action): void
    {
        $handler = $this->resolveHandler($action);

        try {
            $success = $handler->handle($action);

            $action->status = $success
                ? QueueStatusType::STATUS_COMPLETED
                : QueueStatusType::STATUS_FAILED;

            $this->archiveCompletedQueue($action);

            if ($success && $action->action_type === QueueActionType::ACTION_TYPE_MINING) {
                \App\Jobs\AsteroidDepletedJob::dispatch($action->target_id, $action->user_id);
            }

        } catch (\Throwable $e) {
            Log::error("Fehler bei Aktionsverarbeitung", [
                'action_id' => $action->id,
                'error' => $e->getMessage()
            ]);
            $action->status = QueueStatusType::STATUS_FAILED;
        }

        $action->save();
    }

    public function resolveHandler(ActionQueue $action): object
    {
        return match ($action->action_type) {
            QueueActionType::ACTION_TYPE_MINING => app(AsteroidMiningHandler::class),
            QueueActionType::ACTION_TYPE_COMBAT => app(CombatHandler::class),
            QueueActionType::ACTION_TYPE_BUILDING => app(BuildingUpgradeHandler::class),
            default => throw new InvalidArgumentException("No handler for action type {$action->action_type}"),
        };
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
