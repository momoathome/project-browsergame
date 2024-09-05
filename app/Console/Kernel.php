<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * Define the application's command schedule.
   */
  protected function schedule(Schedule $schedule): void
  {
    // Beispiel: Einmal täglich um Mitternacht Asteroiden generieren
/*     $schedule->call(function () {
      $generator = new \App\Services\AsteroidGenerator();
      $generator->generateAsteroids(100);
    })->dailyAt('00:00'); */
  }

  /**
   * Register the commands for the application.
   */
  protected function commands(): void
  {
    $this->load(__DIR__ . '/Commands');

    require_once base_path('routes/console.php');
  }
}
