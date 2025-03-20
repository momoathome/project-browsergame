<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\AsteroidResource;
use App\Models\Resource;
use App\Models\Spacecraft;
use App\Models\User;
use App\Models\Station;
use App\Models\UserResource;
use App\Models\UserAttribute;
use App\Http\Requests\AsteroidExploreRequest;
use App\Dto\ExplorationResult;
use App\Services\QueueService;
use App\Models\ActionQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AsteroidExplorer
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function exploreWithRequest($user, AsteroidExploreRequest $request)
    {
        if (!$request->validated()) {
            return false;
        }

        return $this->asteroidMining(
            $user,
            $request->integer('asteroid_id'),
            $request->collect('spacecrafts')
        );
    }

    public function asteroidMining($user, $asteroidId, $spaceCrafts)
    {
        DB::transaction(function () use ($user, $asteroidId, $spaceCrafts) {
            $filteredSpacecrafts = $this->filterSpacecrafts($spaceCrafts);
            $asteroid = Asteroid::find($asteroidId);
        
            $spacecraftsWithDetails = $this->getSpacecraftsWithDetails($user, $filteredSpacecrafts);
            
            $minerCount = 0;
            foreach ($spacecraftsWithDetails as $spacecraft) {
                if ($spacecraft->details->type === 'Miner') {
                    $count = $filteredSpacecrafts->get($spacecraft->details->name);
                    $minerCount += $count;
                }
            }
    
            // Berechne die Dauer basierend auf dem niedrigsten Speed-Wert und berücksichtige den ActionType
            $duration = $this->calculateTravelDuration($spacecraftsWithDetails, $user, $asteroid, ActionQueue::ACTION_TYPE_MINING, $minerCount);

            $this->lockSpacecrafts($user, $filteredSpacecrafts);
            
            $this->queueService->addToQueue(
                $user->id,
                ActionQueue::ACTION_TYPE_MINING,
                $asteroidId,
                $duration, // Dauer in Sekunden
                [
                    'asteroid_name' => $asteroid->name,
                    'spacecrafts' => $filteredSpacecrafts,
                    'duration' => $duration,
                    'miner_count' => $minerCount
                ]
            );
        });
    }

    public function completeAsteroidMining($asteroidId, $userId, $details)
    {
        $user = User::find($userId);
        $filteredSpacecrafts = $this->filterSpacecrafts($details['spacecrafts']);

        list($totalCargoCapacity, $hasMiner) = $this->calculateCapacityAndMinerStatus($user, $filteredSpacecrafts);

        $asteroid = Asteroid::find($asteroidId);
        if (!$asteroid) {
            $this->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        $asteroidResources = $asteroid->resources()->get();

        list($resourcesExtracted, $remainingResources) = $this->extractResources($asteroidResources, $totalCargoCapacity, $hasMiner);

        $transactionResult = DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted, $details, $filteredSpacecrafts) {
            $this->updateUserResources($user, $resourcesExtracted);
            $this->updateAsteroidResources($asteroid, $remainingResources);

            $this->freeSpacecrafts($user, $filteredSpacecrafts);

            return true;
        });

        if (!$transactionResult) {
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
                    // Wir brauchen nur die Keys der Collection für das whereIn
                    $query->whereIn('name', $filteredSpacecrafts->keys());
                })
                ->lockForUpdate()
                ->get();
    
            foreach ($spacecrafts as $spacecraft) {
                $spacecraft->locked_count = $spacecraft->locked_count ?? 0;
                
                $changeAmount = $filteredSpacecrafts->has($spacecraft->details->name) ? 
                    $filteredSpacecrafts->get($spacecraft->details->name) : 0;
                    
                if ($changeAmount <= 0) {
                    continue;
                }
    
                if ($increment) {
                    // Wenn wir Schiffe freigeben, reduzieren wir locked_count
                    $spacecraft->locked_count = max(0, $spacecraft->locked_count - $changeAmount);
                } else {
                    // Wenn wir Schiffe sperren, erhöhen wir locked_count
                    // Begrenzen Sie die Änderung auf die Anzahl verfügbarer Schiffe
                    $changeAmount = min($changeAmount, $spacecraft->count);
                    $spacecraft->locked_count += $changeAmount;
                }
    
                // Sicherstellen, dass keine negativen Werte existieren
                $spacecraft->count = max(0, $spacecraft->count);
                $spacecraft->locked_count = max(0, $spacecraft->locked_count);
                                
                $spacecraft->save();
            }
    
            return true;
        });
    }

    public function lockSpacecrafts($user, $filteredSpacecrafts)
    {
        return $this->updateSpacecraftCount($user->id, $filteredSpacecrafts, false);
    }

    public function freeSpacecrafts($user, $filteredSpacecrafts)
    {
        return $this->updateSpacecraftCount($user->id, $filteredSpacecrafts, true);
    }

    private function filterSpacecrafts($spaceCrafts)
    {
        $collection = $spaceCrafts instanceof Collection 
            ? $spaceCrafts 
            : collect($spaceCrafts);
        
        $filtered = $collection->filter(function ($count) {
            return $count > 0;
        });
        
        return $filtered;
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

    public function getSpacecraftsWithDetails($user, $filteredSpacecrafts)
    {
        $spacecrafts = Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->whereHas('details', function ($query) use ($filteredSpacecrafts) {
                $query->whereIn('name', $filteredSpacecrafts->keys());
            })
            ->get();
        
        return $spacecrafts;
    }

     public function calculateTravelDuration($spacecrafts, $user, $asteroid, $actionType = null, $minerCount = null)
    {
        $lowestSpeed = 0;
    
        foreach ($spacecrafts as $spacecraft) {
            if ($spacecraft->speed > 0 && ($lowestSpeed === 0 || $spacecraft->speed < $lowestSpeed)) {
                $lowestSpeed = $spacecraft->speed;
            }
        }
    
        $distance = $this->calculateDistanceToAsteroid($user, $asteroid);
        
        // Basisberechnung: Distanz / Geschwindigkeit min 10 Sekunden
        $baseDuration = max(10, round($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1)));
        
        $travelFactor = 1;
        
        // Finale Berechnung der Dauer
        $calculatedDuration = max($baseDuration, (int) ($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1) * $travelFactor));
    
        // Wenn es sich um Mining handelt, reduziere die Dauer basierend auf der Anzahl der Miner
        if ($actionType === ActionQueue::ACTION_TYPE_MINING && $minerCount !== null) {
            if ($minerCount > 0) {
                $calculatedDuration = max(10, (int)($calculatedDuration / $minerCount));
            }
        }
    
        return $calculatedDuration;
    }

    private function calculateDistanceToAsteroid($user, $asteroid)
    {
        $station = Station::where('user_id', $user->id)->first();
        $distance = sqrt(
            pow($station->x - $asteroid->x, 2) +
            pow($station->y - $asteroid->y, 2)
        );

        return (int) round($distance);
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
