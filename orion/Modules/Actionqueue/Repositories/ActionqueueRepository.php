<?php

namespace Orion\Modules\Actionqueue\Repositories;

use Illuminate\Support\Collection;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;

readonly class ActionqueueRepository
{
    public function getUserQueue(int $userId): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->get();
    }

    public function addToQueue(int $userId, QueueActionType $actionType, int $targetId, int $duration, array $details)
    {
        return ActionQueue::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'target_id' => $targetId,
            'start_time' => now(),
            'end_time' => now()->addSeconds($duration),
            'status' => QueueStatusType::STATUS_IN_PROGRESS,
            'details' => $details,
        ]);
    }

    public function getInProgressQueuesFromUserByType(int $userId, QueueActionType $actionType): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->get()
            ->keyBy('target_id');
    }

    public function processQueue(): Collection
    {
        return ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->where('end_time', '<=', now())
            ->get();
    }

    public function processQueueForUser(int $userId): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->where('end_time', '<=', now())
            ->get();
    }

    public function processQueueForUserInstant(int $userId): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->get();
    }
}
