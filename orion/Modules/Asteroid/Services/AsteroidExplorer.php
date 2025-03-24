<?php

namespace Orion\Modules\Asteroid\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;

class AsteroidExplorer
{
    public function __construct(
        private readonly StationService $stationService,
        private readonly ResourceService $resourceService
    ) {
    }
    
    public function calculateCapacityAndMinerStatus(User $user, Collection $filteredSpacecrafts): array
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
    
    public function getSpacecraftsWithDetails($user, Collection $filteredSpacecrafts): Collection
    {
        return $user->spacecrafts()
            ->with('details')
            ->whereHas('details', function ($query) use ($filteredSpacecrafts) {
                $query->whereIn('name', $filteredSpacecrafts->keys());
            })
            ->get();
    }
    
    public function calculateTravelDuration(
        Collection $spacecrafts, 
        $user, 
        Asteroid | Station $asteroid, 
        QueueActionType $actionType = null, 
        ?int $minerCount = null
    ): int {
        $lowestSpeed = 0;
        
        foreach ($spacecrafts as $spacecraft) {
            if ($spacecraft->speed > 0 && ($lowestSpeed === 0 || $spacecraft->speed < $lowestSpeed)) {
                $lowestSpeed = $spacecraft->speed;
            }
        }
        
        $distance = $this->calculateDistanceToAsteroid($user, $asteroid);
        
        $baseDuration = max(10, round($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1)));
        $travelFactor = 1;
        
        $calculatedDuration = max($baseDuration, (int) ($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1) * $travelFactor));
        
        if ($actionType === 'mining' && $minerCount !== null && $minerCount > 0) {
            $calculatedDuration = max(10, (int) ($calculatedDuration / $minerCount));
        }
        
        return $calculatedDuration;
    }
    
    private function calculateDistanceToAsteroid(User $user, Asteroid | Station $asteroid): int
    {
        $station = $this->stationService->findStationByUserId($user->id);
        
        $distance = sqrt(
            pow($station->x - $asteroid->x, 2) +
            pow($station->y - $asteroid->y, 2)
        );
        
        return (int) round($distance);
    }
    
    public function calculateResourceExtraction(Collection $asteroidResources, int $totalCargoCapacity, bool $hasMiner): array
    {
        $resourcesExtracted = [];
        $remainingResources = [];
        $extractionMultiplier = $hasMiner ? 1 : 0.5;
        
        // Calculate total available resources and initial extraction amounts
        $totalAvailable = 0;
        $initialExtraction = [];
        
        foreach ($asteroidResources as $asteroidResource) {
            $resource = $this->resourceService->findResourceByType($asteroidResource->resource_type);
            $availableAmount = min($asteroidResource->amount, floor($totalCargoCapacity * $extractionMultiplier));
            $totalAvailable += $availableAmount;
            $initialExtraction[$resource->id] = $availableAmount;
            $remainingResources[$asteroidResource->resource_type] = $asteroidResource->amount;
        }
        
        // Calculate the extraction ratio if total available exceeds cargo capacity
        $extractionRatio = $totalAvailable > $totalCargoCapacity ? $totalCargoCapacity / $totalAvailable : 1;
        
        // Adjust extraction amounts based on the ratio
        foreach ($initialExtraction as $resourceId => $amount) {
            $extractedAmount = floor($amount * $extractionRatio);
            $resourcesExtracted[$resourceId] = $extractedAmount;
            
            $resourceType = $asteroidResources->first(function ($item) use ($resourceId) {
                return $this->resourceService->findResourceByType($item->resource_type)->id == $resourceId;
            })->resource_type;
            
            $remainingResources[$resourceType] -= $extractedAmount;
        }
        
        return [$resourcesExtracted, $remainingResources];
    }
}
