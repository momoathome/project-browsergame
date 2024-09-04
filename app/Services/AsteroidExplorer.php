<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\AsteroidResource;
use App\Models\Resource;
use App\Models\Spacecraft;
use App\Models\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsteroidExplorer
{
    public function exploreAsteroid($user, $asteroidId, $spaceCrafts)
    {
        $filteredSpacecrafts = array_filter($spaceCrafts, function ($count) {
            return $count > 0;
        });

        $totalCargoCapacity = 0;
        $hasMiner = false;

        $spacecraftsWithDetails = Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->whereHas('details', function ($query) use ($filteredSpacecrafts) {
                $query->whereIn('name', array_keys($filteredSpacecrafts));
            })
            ->get();

        foreach ($spacecraftsWithDetails as $spacecraft) {
            $spacecraftName = $spacecraft->details->name;
            $amountOfSpacecrafts = $filteredSpacecrafts[$spacecraftName];

            if ($spacecraft->count < $amountOfSpacecrafts) {
                return ['error' => 'You do not own enough ' . $spacecraftName . ' spacecrafts.'];
            }

            $totalCargoCapacity += $amountOfSpacecrafts * $spacecraft->cargo;
            if ($spacecraft->details->type === 'Miner') {
                $hasMiner = true;
            }
        }

        $asteroid = Asteroid::findOrFail($asteroidId);
        $asteroidResources = $asteroid->resources()->get();

        Log::info($asteroidResources);

        $resourcesExtracted = [];
        $remainingResources = [];

        foreach ($asteroidResources as $asteroidResource) {
            $resource = Resource::where('name', $asteroidResource->resource_type)->first();
            $resourceId = $resource->id;

            $extractionMultiplier = $hasMiner ? 1 : 0.5;
            $extractedAmount = min($asteroidResource->amount, floor($totalCargoCapacity * $extractionMultiplier));
            $totalCargoCapacity -= $extractedAmount;
            $remainingAmount = $asteroidResource->amount - $extractedAmount;

            $resourcesExtracted[$resourceId] = $extractedAmount;
            $remainingResources[$asteroidResource->resource_type] = max(0, $remainingAmount);

            if ($totalCargoCapacity <= 0) {
                break;
            }
        }

        DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted) {
            foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
                $userResource = UserResource::firstOrNew([
                    'user_id' => $user->id,
                    'resource_id' => $resourceId,
                ]);

                $userResource->amount += $extractedAmount;
                $userResource->save();
            }


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

            if ($asteroid->resources()->count() == 0) {
                $asteroid->delete();
            }
        });

        return redirect()->route('asteroidMap')->banner('Asteroid explored successfully');
    }
}
