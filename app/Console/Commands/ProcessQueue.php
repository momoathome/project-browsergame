<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class ProcessQueue extends Command
{
    protected $signature = 'queue:process';
    protected $description = 'Process the game action queue';

    public function handle(ActionQueueService $queueService)
    {
        $this->info('Processing game action queue...');
        $queueService->processQueue();
        $this->info('Queue processing complete.');
        
        return 0; // 0 bedeutet Erfolg
    }
}
