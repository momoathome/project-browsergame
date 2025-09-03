<?php

namespace App\Console;

use App\Jobs\ProcessActionQueueJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Definiere den Schedule
     */
    protected function schedule(Schedule $schedule): void
    {
        // Führt jede Minute den command aus
        Log::info('Schedule-Methode wird ausgeführt');
        $schedule->command('actionqueue:process')->everyMinute();
    }

    /**
     * Lade die Commands
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
