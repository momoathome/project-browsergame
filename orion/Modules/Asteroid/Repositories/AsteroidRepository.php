<?php

namespace Orion\Modules\Asteroid\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Dto\ExplorationResult;
use Orion\Modules\Asteroid\Models\AsteroidResource;
use Orion\Modules\Logbook\Models\AsteroidMiningLog;

class AsteroidRepository
{
    public function find(int $asteroidId): ?Asteroid
    {
        return Asteroid::find($asteroidId);
    }
    
    public function loadWithResources(Asteroid $asteroid): Asteroid
    {
        return $asteroid->load(['resources']);
    }
    
    public function getAsteroidResources(Asteroid $asteroid): Collection
    {
        return $asteroid->resources()->get();
    }
    
    public function getAllAsteroids(): Collection
    {
        return Asteroid::select('id', 'x', 'y', 'pixel_size')->get();
    }

    public function getAsteroidsInRange(float $centerX, float $centerY, float $scanRange): Collection
    {
        // Hole alle Asteroiden im quadratischen Bereich
        $asteroids = Asteroid::with('resources')
            ->whereBetween('x', [$centerX - $scanRange, $centerX + $scanRange])
            ->whereBetween('y', [$centerY - $scanRange, $centerY + $scanRange])
            ->get();

        // Filtere auf echten Radius (kreisförmig)
        return $asteroids->filter(function ($asteroid) use ($centerX, $centerY, $scanRange) {
            $distance = sqrt(pow($centerX - $asteroid->x, 2) + pow($centerY - $asteroid->y, 2));
            return $distance <= $scanRange;
        })->values();
    }

    public function getRecentAsteroidMines(int $userId, int $limit = 10)
    {
        return AsteroidMiningLog::with(['user:id,name',])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function updateAsteroidResources(Asteroid $asteroid, array $remainingResources): void
    {
        foreach ($remainingResources as $type => $amount) {
            $res = AsteroidResource::where('asteroid_id', $asteroid->id)
                ->where('resource_type', $type)
                ->first();

            if ($amount > 0) {
                $res ? $res->update(['amount' => $amount]) : null;
            } else {
                $res?->delete();
            }
        }

        $this->cleanupAsteroid($asteroid);
    }

    private function cleanupAsteroid(Asteroid $asteroid): void
    {
        $totalAmount = $asteroid->resources()->sum('amount');
        $percentThreshold = max(1, floor($asteroid->value * 0.01)); // mindestens 1%
        $flatThreshold = 40; // mindestens über 40 ressourcen gesamt

        if ($totalAmount < $flatThreshold || $totalAmount < $percentThreshold) {
            $asteroid->delete();
        }
    }

    public function logMiningResult(User $user, Asteroid $asteroid, array $resourcesExtracted, Collection $spacecrafts): void
    {
        AsteroidMiningLog::create([
            'user_id' => $user->id,
            'asteroid_info' => [
                'name' => $asteroid->name,
                'x' => $asteroid->x,
                'y' => $asteroid->y,
                'size' => $asteroid->size,
            ],
            'resources_extracted' => $resourcesExtracted,
            'spacecrafts_used' => $spacecrafts,
        ]);
    }

}
