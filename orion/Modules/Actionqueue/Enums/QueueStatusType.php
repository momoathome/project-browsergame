<?php

namespace Orion\Modules\Actionqueue\Enums;

enum QueueStatusType: string
{
    case STATUS_PENDING = 'pending';
    case STATUS_IN_PROGRESS = 'in_progress';
    case STATUS_PROCESSING = 'processing';
    case STATUS_COMPLETED = 'completed';
    case STATUS_CANCELLED = 'cancelled';
    case STATUS_FAILED = 'failed';
}
