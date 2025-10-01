<?php

namespace Orion\Modules\Asteroid\Repositories;

use Orion\Modules\Asteroid\Models\AsteroidSpawnRequest;
use Illuminate\Support\Collection;

class AsteroidSpawnRequestRepository
{
    public function create(int $asteroidId, int $userId, int $x, int $y): AsteroidSpawnRequest
    {
        return AsteroidSpawnRequest::create([
            'asteroid_id' => $asteroidId,
            'user_id'     => $userId,
            'x'           => $x,
            'y'           => $y,
        ]);
    }

    public function all(): Collection
    {
        return AsteroidSpawnRequest::all();
    }

    public function clear(): void
    {
        AsteroidSpawnRequest::truncate();
    }

    public function allWithUser(): Collection
    {
        return AsteroidSpawnRequest::with('user.stations')->get();
    }

}
