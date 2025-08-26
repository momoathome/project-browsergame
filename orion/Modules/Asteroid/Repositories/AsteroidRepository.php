<?php

namespace Orion\Modules\Asteroid\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Models\AsteroidResource;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\User\Models\UserAttribute;
use Orion\Modules\User\Models\UserResource;

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
