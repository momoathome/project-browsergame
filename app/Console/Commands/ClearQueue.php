<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class ClearQueue extends Command
{
    protected $signature = 'actionqueue:clear';
    protected $description = 'Clears the action queue by setting all in-progress actions to failed';

    protected $actionQueueService;

    public function __construct(ActionQueueService $actionQueueService)
    {
        parent::__construct();
        $this->actionQueueService = $actionQueueService;
    }

    public function handle()
    {
        $queue = $this->actionQueueService->getActionQueue();
        $queue->each(function ($action) {
            $this->actionQueueService->deleteFromQueue($action->id);
        });

    }
}
