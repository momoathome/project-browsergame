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
    public function find(int $id): ?Asteroid
    {
        return Asteroid::where('id', $id)->first();
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

    public function saveAsteroidMiningResult(User $user, Asteroid $asteroid, ExplorationResult $result, $filteredSpacecrafts): void
    {
        AsteroidMiningLog::create([
            'user_id' => $user->id,
            'asteroid_info' => [
                'name' => $asteroid->name,
                'x' => $asteroid->x,
                'y' => $asteroid->y,
                'size' => $asteroid->size,
            ],
            'resources_extracted' => $result->resourcesExtracted,
            'spacecrafts_used' => $filteredSpacecrafts,
        ]);
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
        foreach ($remainingResources as $resourceType => $amount) {
            $asteroidResource = AsteroidResource::where('asteroid_id', $asteroid->id)
                ->where('resource_type', $resourceType)
                ->first();
                
            if ($amount > 0) {
                $asteroidResource->amount = $amount;
                $asteroidResource->save();
            } else {
                $asteroidResource->delete();
            }
        }
        
        $totalAmount = $asteroid->resources()->sum('amount');
        $percentThreshold = max(1, floor($asteroid->value * 0.01)); // mindestens 1%
        $flatThreshold = 40; // mindestens über 40 ressourcen gesamt

        if (
            $asteroid->resources()->count() == 0 ||
            $totalAmount < $flatThreshold ||
            $totalAmount < $percentThreshold
        ) {
            $asteroid->delete();
        }
    }
}
