<?php

namespace App\Console;

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
        $schedule->command('actionqueue:processbatch')->everyMinute();

        $schedule->command('queue:work --sleep=3 --tries=3 --max-time=55')
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/queue.log'));

        $schedule->command('actionqueue:reset-stuck')->everyFiveMinutes();

        $schedule->command('game:rebel-generate-all')->everyFifteenMinutes();

        $schedule->command('game:generate-scheduled-asteroids')->everyFifteenMinutes();
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
