<?php

namespace Orion\Modules\Actionqueue\Dto;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;

class ActionQueueDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly QueueActionType $actionType,
        public readonly int $targetId,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly QueueStatusType $status,
        public readonly array $details,
        public readonly ?string $attackerName = null
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

    public function toArray(): array
    {
        return [
            'queue_id' => $this->id,
            'user_id' => $this->userId,
            'action_type' => $this->actionType,
            'target_id' => $this->targetId,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'attacker_name' => $this->attackerName,
        ];
    }
}
