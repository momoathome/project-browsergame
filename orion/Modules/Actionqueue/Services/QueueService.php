<?php

namespace Orion\Modules\Actionqueue\Services;

use Illuminate\Support\Facades\App;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Repositories\ActionqueueRepository;
use Orion\Modules\Combat\Http\Controllers\CombatController;
use Orion\Modules\Asteroid\Http\Controllers\AsteroidController;
use Orion\Modules\Spacecraft\Http\Controllers\SpacecraftController;

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
        // Je nach Aktionstyp die entsprechende Methode aufrufen
        $success = match ($action->action_type) {
            ActionQueue::ACTION_TYPE_BUILDING => $this->completeBuildingUpgrade($action),
            ActionQueue::ACTION_TYPE_PRODUCE => $this->completeSpacecraftProduction($action),
            ActionQueue::ACTION_TYPE_MINING => $this->completeAsteroidMining($action),            
            ActionQueue::ACTION_TYPE_COMBAT => $this->completeCombat($action),
            // ActionQueue::ACTION_TYPE_RESEARCH => $this->completeResearch($action),
            // ActionQueue::ACTION_TYPE_TRADE => $this->completeTrade($action),
            default => false
        };

        if ($success) {
            $action->status = ActionQueue::STATUS_COMPLETED;
        } else {
            $action->status = ActionQueue::STATUS_FAILED;
        }

        $action->save();
    }

    private function completeBuildingUpgrade(ActionQueue $action)
    {
        $buildingUpgradeService = App::make('Orion\Modules\Building\Services\BuildingUpgradeService');
        return $buildingUpgradeService->completeUpgrade($action->target_id, $action->user_id);
    }

    private function completeSpacecraftProduction(ActionQueue $action)
    {
        $spacecraftController = App::make(SpacecraftController::class);
        $details = $action->details;
        return $spacecraftController->completeProduction($action->target_id, $action->user_id, $details);
    }

    private function completeAsteroidMining(ActionQueue $action)
    {
        $asteroidController = App::make(AsteroidController::class);
        $details = $action->details;
        return $asteroidController->completeAsteroidMining($action->target_id, $action->user_id, $details);
    }

    private function completeCombat(ActionQueue $action)
    {
        $battleController = App::make(CombatController::class);
        $details = $action->details;
        return $battleController->completeCombat($action->user_id, $action->target_id, $details);
    }

/*     private function completeResearch(ActionQueue $action)
    {
        $researchController = App::make(\App\Http\Controllers\ResearchController::class);
        return $researchController->completeResearch($action->target_id, $action->user_id, $action->details);
    } */

    // Weitere Methoden für andere Aktionstypen
}
