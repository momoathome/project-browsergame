<?php

namespace Orion\Modules\Actionqueue\Dto;
use Carbon\Carbon;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;

class ActionQueueDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public QueueActionType $actionType,
        public int $targetId,
        public Carbon $startTime,
        public Carbon $endTime,
        public QueueStatusType $status,
        public array $details,
        public ?string $attackerName = null
    ) {
    }

    public static function fromModel(ActionQueue $actionQueue, ?string $attackerName = null): self
    {
        return new self(
            id: $actionQueue->id,
            userId: $actionQueue->user_id,
            actionType: $actionQueue->action_type,
            targetId: $actionQueue->target_id,
            startTime: $actionQueue->start_time,
            endTime: $actionQueue->end_time,
            status: $actionQueue->status,
            details: $actionQueue->details,
            attackerName: $attackerName
        );
    }
}
