<?php

namespace Orion\Modules\Asteroid\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Resource\Services\ResourceService;

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
        $hasTitan = false;

        $spacecraftsWithDetails = $this->getSpacecraftsWithDetails($user, $filteredSpacecrafts);

        foreach ($spacecraftsWithDetails as $spacecraft) {
            $amountOfSpacecrafts = $filteredSpacecrafts[$spacecraft->details->name];
            $totalCargoCapacity += $amountOfSpacecrafts * $spacecraft->cargo;
            $hasMiner = $hasMiner || ($spacecraft->details->type === 'Miner');
            if (strtolower($spacecraft->details->name) === 'titan' && $amountOfSpacecrafts > 0) {
                $hasTitan = true;
            }
        }

        return [$totalCargoCapacity, $hasMiner, $hasTitan];
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
        Asteroid|Station|Rebel $target,
        ?QueueActionType $actionType = null,
        ?Collection $filteredSpacecrafts = null
    ): int {
        $lowestSpeed = $this->findLowestSpeedOfSpacecrafts($spacecrafts);
        $distance = $this->calculateDistanceToTarget($user, $target);

        $spacecraft_flight_speed = config('game.core.spacecraft_flight_speed');

        // Grundlegende Reisedauer berechnen
        $travelFactor = 1;
        $baseDuration = max(10, round($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1)));
        $calculatedDuration = max($baseDuration, (int) ($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1) * $travelFactor))  / $spacecraft_flight_speed;

        // Aktionstyp-spezifische Zeitberechnung
        if ($actionType !== null) {
            $calculatedDuration = $this->applyOperationSpeedByActionType(
                $calculatedDuration,
                $actionType,
                $spacecrafts,
                $filteredSpacecrafts
            );
        }

        return $calculatedDuration;
    }

    /**
     * Findet die niedrigste Geschwindigkeit in einer Sammlung von Raumschiffen
     */
    private function findLowestSpeedOfSpacecrafts($spacecrafts): int
    {
        $lowestSpeed = 0;
    
        foreach ($spacecrafts as $spacecraft) {
            $speed = is_array($spacecraft) ? ($spacecraft['speed'] ?? 0) : ($spacecraft->speed ?? 0);
            if ($speed > 0 && ($lowestSpeed === 0 || $speed < $lowestSpeed)) {
                $lowestSpeed = $speed;
            }
        }
    
        return $lowestSpeed;
    }

    /**
     * Berechnet die aktionsspezifische Operationsgeschwindigkeit und wendet sie auf die Dauer an
     */
    private function applyOperationSpeedByActionType(
        int $duration,
        QueueActionType $actionType,
        Collection $spacecrafts,
        ?Collection $filteredSpacecrafts = null
    ): int {
        $totalMiningSpeed = $this->calculateTotalOperationSpeedByType($spacecrafts, 'Miner', $filteredSpacecrafts);
        $totalSalvagingSpeed = $this->calculateTotalOperationSpeedByType($spacecrafts, 'Salvager', $filteredSpacecrafts);

        if ($actionType === QueueActionType::ACTION_TYPE_MINING) {
            // Anwendung von diminishing returns auf Mining-Geschwindigkeit
            $effectiveOperationSpeed = $this->applyDiminishingReturns($totalMiningSpeed);
            $result = max(10, (int) ($duration / ($effectiveOperationSpeed / 5)));
            return $result;
        } else if ($actionType === QueueActionType::ACTION_TYPE_SALVAGING) {
            // Anwendung von diminishing returns auf Salvaging-Geschwindigkeit
            $effectiveOperationSpeed = $this->applyDiminishingReturns($totalSalvagingSpeed);
            return max(10, (int) ($duration / ($effectiveOperationSpeed / 5)));
        }

        return $duration;
    }

    /**
     * Wendet abnehmende Rückgabewerte (diminishing returns) auf die Operationsgeschwindigkeit an
     */
    private function applyDiminishingReturns(int $speed): float
    {
        // Basis-Geschwindigkeit (erster Miner/Salvager hat vollen Effekt)
        $baseValue = min(1, $speed);

        // Restliche Geschwindigkeit mit abnehmendem Rückgabewert
        $remainingValue = 0;
        if ($speed > 1) {
            // Logarithmische Funktion für abnehmende Rückgabewerte
            // Wir verwenden log10($speed) + 1, damit der Wert nicht zu stark abfällt
            // Der Faktor 0.5 kann angepasst werden, um die Stärke des abnehmenden Effekts zu steuern
            $remainingValue = 0.85 * (log10($speed) + 1);
        }

        // Kombiniere Basis- und abnehmenden Wert, aber nie unter 1
        return max(1, $baseValue + $remainingValue);
    }

    /**
     * Berechnet die Gesamtoperationsgeschwindigkeit für einen bestimmten Schiffstyp
     */
    private function calculateTotalOperationSpeedByType(
        Collection $spacecrafts,
        string $type,
        ?Collection $filteredSpacecrafts = null
    ): int {
        $totalSpeed = 0;

        foreach ($spacecrafts as $spacecraft) {
            if (isset($spacecraft->details) && $spacecraft->details->type === $type) {
                // Anzahl der Raumschiffe aus den filteredSpacecrafts ermitteln
                $count = 1;

                if ($filteredSpacecrafts && $filteredSpacecrafts->has($spacecraft->details->name)) {
                    $count = $filteredSpacecrafts[$spacecraft->details->name];
                }

                $totalSpeed += ($spacecraft->operation_speed ?? 1) * $count;
            }
        }

        return $totalSpeed;
    }

    private function calculateDistanceToTarget(User $user, Asteroid|Station|Rebel $target): int
    {
        $station = $this->stationService->findStationByUserId($user->id);

        $distance = sqrt(
            pow($station->x - $target->x, 2) +
            pow($station->y - $target->y, 2)
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
