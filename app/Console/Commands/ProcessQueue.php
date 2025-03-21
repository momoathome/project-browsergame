<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Actionqueue\Services\QueueService;

class ProcessQueue extends Command
{
    protected $signature = 'queue:process';
    protected $description = 'Process the game action queue';

    public function handle(QueueService $queueService)
    {
        $this->info('Processing game action queue...');
        $queueService->processQueue();
        $this->info('Queue processing complete.');
        
        return 0; // 0 bedeutet Erfolg
    }
}
