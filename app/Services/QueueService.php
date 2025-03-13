<?php

namespace App\Services;

use App\Models\ActionQueue;
use Illuminate\Support\Facades\App;

class QueueService
{
    public function addToQueue($userId, $actionType, $targetId, $duration, $details)
    {
        return ActionQueue::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'target_id' => $targetId,
            'start_time' => now(),
            'end_time' => now()->addSeconds($duration),
            'status' => 'pending',
            'details' => $details,
        ]);
    }

    public function processQueue()
    {
        $completedActions = ActionQueue::where('status', 'pending')
            ->where('end_time', '<=', now())
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
            // ActionQueue::ACTION_TYPE_PRODUCE => $this->completeSpacecraftBuild($action),
            // ActionQueue::ACTION_TYPE_MINING => $this->completeAsteroidFarming($action),
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
        $buildingController = App::make(\App\Http\Controllers\BuildingController::class);
        return $buildingController->completeUpgrade($action->target_id, $action->user_id);
    }

    // Weitere Methoden für andere Aktionstypen
}
