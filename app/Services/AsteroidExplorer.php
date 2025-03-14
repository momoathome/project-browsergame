<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\AsteroidResource;
use App\Models\Resource;
use App\Models\Spacecraft;
use App\Models\User;
use App\Models\UserResource;
use App\Models\UserAttribute;
use App\Http\Requests\AsteroidExploreRequest;
use App\Dto\ExplorationResult;
use App\Services\QueueService;
use App\Models\ActionQueue;
use Illuminate\Support\Facades\DB;

class AsteroidExplorer
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function exploreWithRequest($user, AsteroidExploreRequest $request)
    {
        return $this->asteroidMining(
            $user,
            $request->getAsteroidId(),
            $request->getSpacecrafts()
        );
    }

    public function asteroidMining($user, $asteroidId, $spaceCrafts)
    {
        DB::transaction(function () use ($user, $asteroidId, $spaceCrafts) {
            $filteredSpacecrafts = $this->filterSpacecrafts($spaceCrafts);

            $this->queueService->addToQueue(
                $user->id,
                ActionQueue::ACTION_TYPE_MINING,
                $asteroidId,
                10, // Dauer in Sekunden
                [
                    'asteroid_id' => $asteroidId,
                    'spacecrafts' => $filteredSpacecrafts
                ]
            );

            // spacecrafts für die dauer der exploration sperren
            $this->lockSpacecrafts($user, $filteredSpacecrafts);
        });
    }

    public function completeAsteroidMining($asteroidId, $userId, $details)
    {
        $user = User::find($userId);
        $filteredSpacecrafts = $this->filterSpacecrafts($details['spacecrafts']);

        list($totalCargoCapacity, $hasMiner) = $this->calculateCapacityAndMinerStatus($user, $filteredSpacecrafts);

        $asteroid = Asteroid::find($asteroidId);
        if (!$asteroid) {
            // Raumschiffe freigeben
            $this->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        $asteroidResources = $asteroid->resources()->get();

        list($resourcesExtracted, $remainingResources) = $this->extractResources($asteroidResources, $totalCargoCapacity, $hasMiner);

        $transactionResult = DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted, $details, $filteredSpacecrafts) {
            $this->updateUserResources($user, $resourcesExtracted);
            $this->updateAsteroidResources($asteroid, $remainingResources);

            // Raumschiffe freigeben
            $this->freeSpacecrafts($user, $filteredSpacecrafts);

            return true;
        });

        if (!$transactionResult) {
            // Raumschiffe freigeben
            $this->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        return new ExplorationResult(
            $resourcesExtracted,
            $totalCargoCapacity,
            $asteroid->id,
            $hasMiner
        );
    }

    private function updateSpacecraftCount($userId, $filteredSpacecrafts, $increment = false)
{
    return DB::transaction(function () use ($userId, $filteredSpacecrafts, $increment) {
        $spacecrafts = Spacecraft::where('user_id', $userId)
            ->whereHas('details', function ($query) use ($filteredSpacecrafts) {
                $query->whereIn('name', array_keys($filteredSpacecrafts));
            })
            ->lockForUpdate()
            ->get();
        
        foreach ($spacecrafts as $spacecraft) {
            $changeAmount = $filteredSpacecrafts[$spacecraft->details->name];
            
            if ($increment) {
                $spacecraft->count += $changeAmount;
            } else {
                $spacecraft->count -= $changeAmount;
            }
            
            $spacecraft->save();
        }
        
        return true;
    });
}

    private function lockSpacecrafts($user, $filteredSpacecrafts) 
    {
        return $this->updateSpacecraftCount($user->id, $filteredSpacecrafts, false);
    }
    
    private function freeSpacecrafts($user, $filteredSpacecrafts)
    {
        return $this->updateSpacecraftCount($user->id, $filteredSpacecrafts, true);
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

        // calculate total available resources and initial extraction amounts
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

        // adjust extraction amounts based on the ratio
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

        $storageCapacity = $userStorageAttribute->attribute_value;

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
