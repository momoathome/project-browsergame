<?php

namespace App\Services;

use App\Models\ActionQueue;
use Carbon\Carbon;

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
        // Implementieren Sie hier die Logik für die Aktionsabschlüsse
        // z.B. Ressourcen hinzufügen, Gebäude upgraden usw.
        $action->status = 'completed';
        $action->save();
    }
}
