<?php

namespace Orion\Modules\Asteroid\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Events\ReloadFrontendCanvas;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;
use Orion\Modules\Asteroid\Repositories\AsteroidSpawnRequestRepository;

readonly class AsteroidSpawnRequestService
{
    public function __construct(
        private readonly AsteroidSpawnRequestRepository $repository,
        private readonly AsteroidGenerator $asteroidGenerator,
        private readonly UserAttributeService $userAttributeService,
    ) {
    }

    public function processRequestedAsteroidSpawns()
    {
        $requests = $this->repository->all();

        // Gruppiere die Requests nach User-ID
        $grouped = [];
        foreach ($requests as $request) {
            $userId = $request->user->id;
            $grouped[$userId][] = $request;
        }

        $newAsteroids = [];
        foreach ($grouped as $userRequests) {
            $user = $userRequests[0]->user;
            $station = $user->stations->first();
            $scanRange = 6000 + $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::SCAN_RANGE)->attribute_value ?? 0;

            // Anzahl der Requests = Anzahl der zu generierenden Asteroiden
            $count = count($userRequests);

            $asteroids = $this->asteroidGenerator->generateAsteroids(
                $count,
                $station->x,
                $station->y,
                $scanRange
            );

            $newAsteroids = array_merge($newAsteroids, $asteroids);
        }

        $this->repository->clear();

        $filteredNewAsteroids = array_map(function($a) {
            return [
                'id' => $a['id'],
                'x' => $a['x'],
                'y' => $a['y'],
                'pixel_size' => $a['pixel_size'],
            ];
        }, $newAsteroids);

        $chunkSize = 50;
        $chunks = array_chunk($filteredNewAsteroids, $chunkSize);

        foreach ($chunks as $chunk) {
            broadcast(new ReloadFrontendCanvas(null, $chunk));
        }
        return $newAsteroids;
    }

}
