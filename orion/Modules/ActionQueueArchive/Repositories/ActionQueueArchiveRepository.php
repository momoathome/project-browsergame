<?php

namespace Orion\Modules\ActionQueueArchive\Repositories;

use Orion\Modules\ActionQueueArchive\Models\ActionQueueArchive;
use Illuminate\Support\Collection;

readonly class ActionQueueArchiveRepository
{
    // Add repository logic here
    public function createArchiveEntry($action)
    {
        return ActionQueueArchive::create([
            'user_id' => $action->user_id,
            'action_type' => $action->action_type,
            'target_id' => $action->target_id,
            'start_time' => $action->start_time,
            'end_time' => $action->end_time,
            'status' => $action->status,
            'details' => $action->details,
        ]);
    }

    public function getArchiveEntriesByUserId($userId): Collection
    {
        return ActionQueueArchive::where('user_id', $userId)->get();
    }

    public function getArchiveEntriesByUserIdAndActionType($userId, $actionType): Collection
    {
        return ActionQueueArchive::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->get();
    }

    public function getAllArchiveEntries(): Collection
    {
        return ActionQueueArchive::all();
    }
}
