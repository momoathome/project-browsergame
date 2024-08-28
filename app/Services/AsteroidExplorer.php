<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\Resource;
use App\Models\Spacecraft;
use App\Models\UserResource;
use Illuminate\Support\Facades\DB;

class AsteroidExplorer
{
    public function exploreAsteroid($user, $asteroidId, $spaceCrafts)
    {
        $filteredSpacecrafts = array_filter($spaceCrafts, function ($count) {
            return $count > 0;
        });

        $totalCargoCapacity = 0;
        $hasMiner = false;

        $spacecraftsWithDetails = Spacecraft::join('spacecraft_details', 'spacecrafts.details_id', '=', 'spacecraft_details.id')
            ->where('user_id', $user->id)
            ->whereIn('spacecraft_details.name', array_keys($filteredSpacecrafts))
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
        $asteroidResources = json_decode($asteroid->resources, true);
        $resourceCount = count($asteroidResources);

        $resourcesExtracted = [];
        $remainingResources = [];

        foreach ($asteroidResources as $resourceName => $amount) {
            $resource = Resource::where('name', $resourceName)->first();
            $resourceId = $resource->id;

            $extractionMultiplier = $hasMiner ? 1 : 0.5;
            $extractedAmount = min($amount, floor($totalCargoCapacity / $resourceCount * $extractionMultiplier));
            $remainingAmount = max(0, $amount - $extractedAmount);

            $resourcesExtracted[$resourceId] = $extractedAmount;
            $remainingResources[$resourceName] = $remainingAmount;
        }

        DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted) {
            foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
                $userResource = UserResource::firstOrNew([
                    'user_id' => $user->id,
                    'resource_id' => $resourceId,
                ]);

                $userResource->count += $extractedAmount;
                $userResource->save();
            }

            if (empty(array_filter($remainingResources))) {
                $asteroid->delete();
            } else {
                $asteroid->resources = json_encode($remainingResources);
                $asteroid->save();
            }
        });

        return redirect()->route('asteroidMap')->banner('Asteroid explored successfully');
    }
}
