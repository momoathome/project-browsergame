<?php
namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class ProcessActionQueueBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public array $queueEntryIds;

    public function __construct(array $queueEntryIds)
    {
        $this->queueEntryIds = $queueEntryIds;
    }

    public function handle(ActionQueueService $service)
    {
        $entries = ActionQueue::whereIn('id', $this->queueEntryIds)->get();
        foreach ($entries as $entry) {
            $service->completeAction($entry);
        }
    }
}
