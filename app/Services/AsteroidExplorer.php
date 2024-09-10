<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\AsteroidResource;
use App\Models\Resource;
use App\Models\Spacecraft;
use App\Models\UserResource;
use App\Models\UserAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsteroidExplorer
{
    public function exploreAsteroid($user, $asteroidId, $spaceCrafts)
    {
        $filteredSpacecrafts = $this->filterSpacecrafts($spaceCrafts);

        list($totalCargoCapacity, $hasMiner) = $this->calculateCapacityAndMinerStatus($user, $filteredSpacecrafts);

        $asteroid = Asteroid::findOrFail($asteroidId);
        $asteroidResources = $asteroid->resources()->get();

        list($resourcesExtracted, $remainingResources) = $this->extractResources($asteroidResources, $totalCargoCapacity, $hasMiner);

        DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted) {
            $this->updateUserResources($user, $resourcesExtracted);
            $this->updateAsteroidResources($asteroid, $remainingResources);
        });

        return redirect()->route('asteroidMap')->banner('Asteroid explored successfully');
    }

    private function filterSpacecrafts($spaceCrafts)
    {
        return array_filter($spaceCrafts, function ($count) {
            return $count > 0;
        });
    }

    private function calculateCapacityAndMinerStatus($user, $filteredSpacecrafts)
    {
        $totalCargoCapacity = 0;
        $hasMiner = false;

        $spacecraftsWithDetails = $this->getSpacecraftsWithDetails($user, $filteredSpacecrafts);

        foreach ($spacecraftsWithDetails as $spacecraft) {
            $amountOfSpacecrafts = $filteredSpacecrafts[$spacecraft->details->name];

            if ($spacecraft->count < $amountOfSpacecrafts) {
                throw new \Exception('You do not own enough ' . $spacecraft->details->name . ' spacecrafts.');
            }

            $totalCargoCapacity += $amountOfSpacecrafts * $spacecraft->cargo;
            $hasMiner = $hasMiner || ($spacecraft->details->type === 'Miner');
        }

        return [$totalCargoCapacity, $hasMiner];
    }

    private function getSpacecraftsWithDetails($user, $filteredSpacecrafts)
    {
        return Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->whereHas('details', function ($query) use ($filteredSpacecrafts) {
                $query->whereIn('name', array_keys($filteredSpacecrafts));
            })
            ->get();
    }

    private function extractResources($asteroidResources, $totalCargoCapacity, $hasMiner)
    {
        $resourcesExtracted = [];
        $remainingResources = [];
        $extractionMultiplier = $hasMiner ? 1 : 0.5;

        // First: calculate total available resources and initial extraction amounts
        $totalAvailable = 0;
        $initialExtraction = [];
        foreach ($asteroidResources as $asteroidResource) {
            $resource = Resource::where('name', $asteroidResource->resource_type)->first();
            $availableAmount = min($asteroidResource->amount, floor($totalCargoCapacity * $extractionMultiplier));
            $totalAvailable += $availableAmount;
            $initialExtraction[$resource->id] = $availableAmount;
            $remainingResources[$asteroidResource->resource_type] = $asteroidResource->amount;
        }

        // Calculate the extraction ratio if total available exceeds cargo capacity
        $extractionRatio = $totalAvailable > $totalCargoCapacity ? $totalCargoCapacity / $totalAvailable : 1;

        // Second: adjust extraction amounts based on the ratio
        foreach ($initialExtraction as $resourceId => $amount) {
            $extractedAmount = floor($amount * $extractionRatio);
            $resourcesExtracted[$resourceId] = $extractedAmount;
            $resourceType = $asteroidResources->first(function ($item) use ($resourceId) {
                return Resource::where('name', $item->resource_type)->first()->id == $resourceId;
            })->resource_type;
            $remainingResources[$resourceType] -= $extractedAmount;
        }

        return [$resourcesExtracted, $remainingResources];
    }

    private function updateUserResources($user, $resourcesExtracted)
    {
        $userStorageAttribute = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'storage')
            ->first();

        $storageCapacity = $userStorageAttribute ? $userStorageAttribute->attribute_value : 0;

        foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
            $userResource = UserResource::firstOrNew([
                'user_id' => $user->id,
                'resource_id' => $resourceId,
            ]);

            $availableStorage = $storageCapacity - $userResource->amount;
            $amountToAdd = min($extractedAmount, $availableStorage);

            $userResource->amount += $amountToAdd;
            $userResource->save();
        }
    }

    private function updateAsteroidResources($asteroid, $remainingResources)
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

        if ($asteroid->resources()->count() == 0) {
            $asteroid->delete();
        }
    }
}
