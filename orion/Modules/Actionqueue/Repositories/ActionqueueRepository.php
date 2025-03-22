<?php

namespace Orion\Modules\Actionqueue\Repositories;

use Orion\Modules\Actionqueue\Models\ActionQueue;

readonly class ActionqueueRepository
{
    public function getUserQueue(int $userId)
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->get();
    }

    public function addToQueue(int $userId, string $actionType, int $targetId, int $duration, array $details)
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

    public function getInProgressQueuesFromUserByType(int $userId, string $actionType)
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->get()
            ->keyBy('target_id');
    }

    public function processQueue()
    {
        return ActionQueue::where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->where('end_time', '<=', now())
            ->get();
    }

    public function processQueueForUser(int $userId)
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->where('end_time', '<=', now())
            ->get();
    }

    public function processQueueForUserInstant(int $userId)
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', ActionQueue::STATUS_IN_PROGRESS)
            ->get();
    }
}
