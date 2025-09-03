<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class ProcessQueue extends Command
{
    protected $signature = 'actionqueue:processing';
    protected $description = 'Process the game action queue';

    public function handle(ActionQueueService $queueService)
    {
        Log::info('actionqueue:processing Command gestartet: ' . now());
        $queueService->processQueue();
        return 0;
    }
}
