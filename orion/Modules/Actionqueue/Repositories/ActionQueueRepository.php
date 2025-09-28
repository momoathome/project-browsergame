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

    public function getActionQueue(): Collection
    {
        return ActionQueue::query()
            ->whereIn('status', [
                QueueStatusType::STATUS_IN_PROGRESS,
                QueueStatusType::STATUS_PROCESSING
            ])
            ->get();
    }

    public function getUserQueue(int $userId): Collection
    {
        $userQueue = ActionQueue::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                QueueStatusType::STATUS_PENDING,
                QueueStatusType::STATUS_IN_PROGRESS,
            ])
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

    public function getInProgressQueuesFromUserByType(int $userId, QueueActionType $actionType): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->get()
            ->keyBy('target_id');
    }

    public function getInProgressQueuesByUser(int $userId): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->whereIn('status', [
                QueueStatusType::STATUS_IN_PROGRESS,
                QueueStatusType::STATUS_PROCESSING
            ])
            ->get();
    }

    public function getQueuesFromUserByType(int $userId, QueueActionType $actionType): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->whereIn('status', [
                QueueStatusType::STATUS_IN_PROGRESS,
                QueueStatusType::STATUS_PENDING
            ])
            ->get();
    }

    public function addToQueue(int $userId, QueueActionType $actionType, int $targetId, int $duration, array $details, QueueStatusType $status = QueueStatusType::STATUS_IN_PROGRESS): ActionQueue
    {
        return DB::transaction(function () use ($userId, $actionType, $targetId, $duration, $details, $status) {
            // Prüfe, ob bereits ein IN_PROGRESS für das Ziel existiert
            $exists = ActionQueue::where('user_id', $userId)
                ->where('action_type', $actionType)
                ->where('target_id', $targetId)
                ->whereIn('status', [
                    QueueStatusType::STATUS_IN_PROGRESS,
                    QueueStatusType::STATUS_PENDING
                ])
                ->lockForUpdate()
                ->exists();
    
            if ($exists && $actionType !== QueueActionType::ACTION_TYPE_MINING) {
                $status = QueueStatusType::STATUS_PENDING;
            }

            return ActionQueue::create([
                'user_id' => $userId,
                'action_type' => $actionType,
                'target_id' => $targetId,
                'start_time' => now(),
                'end_time' => now()->addSeconds($duration),
                'status' => $status,
                'details' => $details,
            ]);
        });
    }

    public function promoteNextPending(int $userId, QueueActionType $actionType, int $targetId): void
    {
        // Hole alle Pending-Einträge für den User und ActionType, sortiert nach Erstellungsdatum
        $pendingEntries = ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('status', QueueStatusType::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
    
        foreach ($pendingEntries as $pending) {
            // Prüfe, ob für dieses Gebäude bereits ein IN_PROGRESS existiert
            $exists = ActionQueue::where('user_id', $userId)
                ->where('action_type', $actionType)
                ->where('target_id', $pending->target_id)
                ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
                ->lockForUpdate()
                ->exists();
    
            if (!$exists) {
                $duration = $pending->details['duration'] ?? 60;
                $pending->update([
                    'status' => QueueStatusType::STATUS_IN_PROGRESS,
                    'start_time' => now(),
                    'end_time' => now()->addSeconds($duration),
                ]);
                break; // Nur das erste passende Pending promoten
            }
        }
    }

    public function create(array $data): ActionQueue
    {
        return ActionQueue::create($data);
    }

    public function countInProgressProduceByUser(int $userId): int
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', QueueActionType::ACTION_TYPE_PRODUCE)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->count();
    }

    public function countInProgressBuildingByUser(int $userId): int
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', QueueActionType::ACTION_TYPE_BUILDING)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->count();
    }

    public function countInProgressBuildingByUserAndTarget(int $userId, int $targetId): int
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', QueueActionType::ACTION_TYPE_BUILDING)
            ->where('target_id', $targetId)
            ->where('status', QueueStatusType::STATUS_IN_PROGRESS)
            ->count();
    }

    public function getFirstPendingProduceByUser(int $userId): ?ActionQueue
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', QueueActionType::ACTION_TYPE_PRODUCE)
            ->where('status', QueueStatusType::STATUS_PENDING)
            ->orderBy('created_at')
            ->first();
    }

    public function getFirstPendingBuildingByUserAndTarget(int $userId, int $targetId): ?ActionQueue
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', QueueActionType::ACTION_TYPE_BUILDING)
            ->where('target_id', $targetId)
            ->where('status', QueueStatusType::STATUS_PENDING)
            ->orderBy('created_at')
            ->first();
    }

    public function update(ActionQueue $queue, array $data): bool
    {
        return $queue->update($data);
    }

    public function delete(int $id): bool
    {
        return ActionQueue::where('id', $id)->delete() > 0;
    }

    public function getQueuedUpgrades(int $userId, int $targetId, QueueActionType $actionType): Collection
    {
        return ActionQueue::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('target_id', $targetId)
            ->whereIn('status', [
                QueueStatusType::STATUS_IN_PROGRESS,
                QueueStatusType::STATUS_PENDING
            ])
            ->get();
    }

    public function processQueue(): Collection
    {
        return DB::transaction(function () {
            // Zuerst Status updaten (ohne get)
            ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
                ->where('end_time', '<=', now())
                ->lockForUpdate()
                ->update(['status' => QueueStatusType::STATUS_PROCESSING]);

            // Dann die aktualisierten Datensätze holen
            return ActionQueue::where('status', QueueStatusType::STATUS_PROCESSING)
                ->where('end_time', '<=', now())
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
                ->update(['status' => QueueStatusType::STATUS_PROCESSING]);
    
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
