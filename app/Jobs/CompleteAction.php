<?php

namespace App\Jobs;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompleteActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $actionId) {}

    public function handle(ActionQueueService $queueService)
    {
        $action = ActionQueue::find($this->actionId);
        if (!$action) {
            return;
        }

        $queueService->completeAction($action);
    }
}
