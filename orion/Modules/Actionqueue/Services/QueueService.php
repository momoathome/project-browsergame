<?php

namespace Orion\Modules\Actionqueue\Services;

use Illuminate\Support\Facades\App;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Handlers\BuildingUpgradeHandler;
use Orion\Modules\Actionqueue\Handlers\SpacecraftProductionHandler;
use Orion\Modules\Actionqueue\Handlers\AsteroidMiningHandler;
use Orion\Modules\Actionqueue\Handlers\CombatHandler;
use Orion\Modules\Actionqueue\Repositories\ActionqueueRepository;

class QueueService
{
    public function __construct(
        private readonly ActionqueueRepository $actionqueueRepository,
    ) {
    }
    public function getUserQueue($userId)
    {
        return $this->actionqueueRepository->getUserQueue($userId);
    }

    public function addToQueue($userId, $actionType, $targetId, $duration, $details)
    {
        return $this->actionqueueRepository->addToQueue($userId, $actionType, $targetId, $duration, $details);
    }

    public function getInProgressQueuesFromUserByType($userId, $actionType)
    {
        return $this->actionqueueRepository->getInProgressQueuesFromUserByType($userId, $actionType);
    }

    public function processQueue()
    {
        $completedActions = $this->actionqueueRepository->processQueue();

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUser($userId)
    {
        $completedActions = $this->actionqueueRepository->processQueueForUser($userId);

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUserInstant($userId)
    {
        // check for admin role
        $completedActions = $this->actionqueueRepository->processQueueForUserInstant($userId);

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    private function completeAction(ActionQueue $action)
    {
        // Handlerklassen für verschiedene Aktionstypen
        $handler = match ($action->action_type) {
            ActionQueue::ACTION_TYPE_BUILDING => App::make(BuildingUpgradeHandler::class),
            ActionQueue::ACTION_TYPE_PRODUCE => App::make(SpacecraftProductionHandler::class),
            ActionQueue::ACTION_TYPE_MINING => App::make(AsteroidMiningHandler::class),            
            // ActionQueue::ACTION_TYPE_COMBAT => App::make(CombatHandler::class),
            default => null
        };

        $success = $handler ? $handler->handle($action) : false;

        if ($success) {
            $action->status = ActionQueue::STATUS_COMPLETED;
        } else {
            $action->status = ActionQueue::STATUS_FAILED;
        }

        $action->save();
    }

}
