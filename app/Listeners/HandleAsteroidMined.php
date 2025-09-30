<?php

namespace App\Listeners;

use App\Events\AsteroidMined;
use Illuminate\Support\Facades\Log;
use App\Events\ReloadFrontendCanvas;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;

class HandleAsteroidMined
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AsteroidMined $event): void
    {
        try {
            app(AsteroidGenerator::class)->generateAsteroids(
                rand(0, 2),
                $event->asteroid->x,
                $event->asteroid->y,
                15000
            );
            Log::info('Asteroids generated successfully');
        } catch (\Exception $e) {
            Log::error('Asteroid generation failed: '.$e->getMessage());
        }

        broadcast(new ReloadFrontendCanvas($event->asteroid));
    }
}

