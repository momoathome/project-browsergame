<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;

use App\Events\ReloadFrontendCanvas;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Modules\Asteroid\Services\AsteroidService;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;

class AsteroidDepletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $asteroidId, public int $userId) {}

    public function handle(AsteroidGenerator $generator, AsteroidService $asteroidService)
    {
        // neue Asteroiden erzeugen
        $generator->generateAsteroids(rand(0, 2), null, null, 25000);

        // alten Asteroid laden (mit Resources)
        $asteroid = $asteroidService->find($this->asteroidId);

        if ($asteroid) {
            // Broadcast mit Asteroid-Modell
            broadcast(new ReloadFrontendCanvas($asteroid));
        }
    }
}
