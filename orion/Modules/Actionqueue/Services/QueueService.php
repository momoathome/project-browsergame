<?php

namespace Orion\Modules\Actionqueue\Services;

use Illuminate\Support\Facades\App;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Asteroid\Http\Controllers\AsteroidController;
use Orion\Modules\Building\Http\Controllers\BuildingController;
use Orion\Modules\Spacecraft\Http\Controllers\SpacecraftController;
use Orion\Modules\Combat\Http\Controllers\CombatController;

class QueueService
{

    public function getPlayerQueue($userId)
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->get();
    }

    public function addToQueue($userId, $actionType, $targetId, $duration, $details)
    {
        return ActionQueue::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'target_id' => $targetId,
            'start_time' => now(),
            'end_time' => now()->addSeconds($duration),
            'status' => ActionQueue::STATUS_IN_PROGRESS,
            'details' => $details,
        ]);
    }

    /**
     * Get in progress queues for a specific user and action type
     *
     * @param int $userId
     * @param string $actionType
     * @return \Illuminate\Support\Collection
     */
    public function getInProgressQueuesByType($userId, $actionType)
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->get()
            ->keyBy('target_id');
    }

    public function processQueue()
    {
        $completedActions = ActionQueue::where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->where('end_time', '<=', now())
            ->get();

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUser($userId)
    {
        $completedActions = ActionQueue::where('user_id', $userId)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->where('end_time', '<=', now())
            ->get();

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUserInstant($userId)
    {
        // check for admin role
        $completedActions = ActionQueue::where('user_id', $userId)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->get();

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
        $buildingController = App::make(BuildingController::class);
        return $buildingController->completeUpgrade($action->target_id, $action->user_id);
    }

    private function completeSpacecraftProduction(ActionQueue $action)
    {
        $spacecraftController = App::make(SpacecraftController::class);
        return $spacecraftController->completeProduction($action->target_id, $action->user_id, $action->details);
    }

    private function completeAsteroidMining(ActionQueue $action)
    {
        $asteroidController = App::make(AsteroidController::class);
        return $asteroidController->completeAsteroidMining($action->target_id, $action->user_id, $action->details);
    }

    private function completeCombat(ActionQueue $action)
    {
        $battleController = App::make(CombatController::class);
        return $battleController->completeCombat($action->user_id, $action->target_id, $action->details);
    }

/*     private function completeResearch(ActionQueue $action)
    {
        $researchController = App::make(\App\Http\Controllers\ResearchController::class);
        return $researchController->completeResearch($action->target_id, $action->user_id, $action->details);
    } */

    // Weitere Methoden für andere Aktionstypen
}
