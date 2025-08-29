<?php

namespace Orion\Modules\Actionqueue\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Actionqueue\Dto\ActionQueueDTO;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;

readonly class ActionQueueRepository
{
    public function getUserQueue(int $userId): Collection
    {
        $userQueue = ActionQueue::query()
            ->where('user_id', $userId)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->orderBy('start_time', 'asc');

        $defendQueue = ActionQueue::query()
            ->where('target_id', $userId)
            ->where('action_type', QueueActionType::ACTION_TYPE_COMBAT)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->orderBy('end_time', 'asc');

        $result = $userQueue->union($defendQueue)->get();
        $returnObject = $result->map(fn($item) => ActionQueueDTO::fromModel($item));
        
        return $returnObject;
    }

    public function addToQueue(int $userId, QueueActionType $actionType, int $targetId, int $duration, array $details)
    {
        return DB::transaction(function () use ($userId, $actionType, $targetId, $duration, $details) {
            $exists = ActionQueue::where('user_id', $userId)
                ->where('action_type', $actionType)
                ->where('target_id', $targetId)
                ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
                ->lockForUpdate()
                ->exists();
    
            if ($exists) {
                return null;
            }
    
            return ActionQueue::create([
                'user_id' => $userId,
                'action_type' => $actionType,
                'target_id' => $targetId,
                'start_time' => now(),
                'end_time' => now()->addSeconds($duration),
                'status' => QueueStatusType::STATUS_IN_PROGRESS,
                'details' => $details,
            ]);
        });
    }

    public function deleteFromQueue($id)
    {
        return ActionQueue::where('id', $id)
            ->delete();
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
        return DB::transaction(function () {
            return ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
                ->where('end_time', '<=', now())
                ->lockForUpdate()
                ->get();
        });
    }

    public function processQueueForUser($userId): Collection
    {
        return DB::transaction(function () use ($userId) {
            // Atomar claimen: Status auf PROCESSING setzen und nur die IDs zurückgeben
            $affected = ActionQueue::where('user_id', $userId)
                ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
                ->where('end_time', '<=', now())
                ->lockForUpdate()
                ->update(['status' => QueueStatusType::STATUS_PROCESSING]);

            if ($affected > 0) {
                Log::info("Claimed $affected queue items for user $userId");
            }
    
            // Jetzt alle "geclaimten" Actions holen
            return ActionQueue::where('user_id', $userId)
                ->where('status', QueueStatusType::STATUS_PROCESSING)
                ->where('end_time', '<=', now())
                ->get();
        });
    }

    public function processQueueForUserInstant(int $userId): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->lockForUpdate()
            ->get();
    }
}
