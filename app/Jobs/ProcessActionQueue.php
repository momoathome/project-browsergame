<?php
namespace App\Jobs;

use \Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class ProcessActionQueue implements ShouldQueue
{
    public $queueEntryId;

    use Dispatchable, InteractsWithQueue;  

    public function __construct($queueEntryId)
    {
        $this->queueEntryId = $queueEntryId;
    }

    public function handle(ActionQueueService $service)
    {
        $entry = ActionQueue::find($this->queueEntryId);
        if ($entry) {
            $service->completeAction($entry);
        }
    }
}
