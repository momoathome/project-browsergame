<?php

namespace Orion\Modules\Spacecraft\Services;

use Orion\Modules\Actionqueue\Models\ActionQueueSpacecraftLock;
use Illuminate\Support\Collection;

class SpacecraftLockService
{
    /**
     * Setzt Locks für einen Auftrag
     */
    public function lockForQueue(int $actionQueueId, Collection $spacecrafts): void
    {
        foreach ($spacecrafts as $detailsId => $amount) {
            ActionQueueSpacecraftLock::create([
                'action_queue_id' => $actionQueueId,
                'spacecraft_details_id' => $detailsId,
                'amount' => $amount,
            ]);
        }
    }

    /**
     * Gibt alle Locks für einen Auftrag wieder frei
     */
    public function freeForQueue(int $actionQueueId): void
    {
        ActionQueueSpacecraftLock::where('action_queue_id', $actionQueueId)->delete();
    }

    /**
     * Gibt alle Locks für einen User zurück (z.B. für Übersicht)
     */
    public function getLocksForUser(int $userId): Collection
    {
        return ActionQueueSpacecraftLock::whereHas('actionQueue', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();
    }
}
