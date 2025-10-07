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

    public function calculateCapacityAndMinerStatus(User $user, Collection $spacecrafts): array
    {
        $totalCargo = 0;
        $hasMiner = false;
        $hasTitan = false;

        $spacecraftsWithDetails = $this->getSpacecraftsWithDetails($user, $spacecrafts);

        foreach ($spacecraftsWithDetails as $spacecraft) {
            $amount = $spacecrafts[$spacecraft->details->name];
            $totalCargo += $amount * $spacecraft->cargo;
            $hasMiner = $hasMiner || ($spacecraft->details->type === 'Miner');
            $hasTitan = $hasTitan || (strtolower($spacecraft->details->name) === 'titan' && $amount > 0);
        }

        return [$totalCargo, $hasMiner, $hasTitan];
    }

    public function extractResources(Asteroid $asteroid, int $capacity, bool $hasMiner, bool $hasTitan): array
    {
        $resources = $asteroid->resources()->get();

        // Titan benötigt für extreme Asteroiden
        if ($asteroid->size === 'extreme' && !$hasTitan) {
            return [[], $resources->pluck('amount', 'resource_type')->toArray()];
        }

        $multiplier = $hasMiner ? 1 : 0.5;
        $resourcesExtracted = [];
        $remainingResources = [];

        $totalAvailable = $resources->sum(fn($res) => min($res->amount, floor($capacity * $multiplier)));

        $ratio = $totalAvailable > $capacity ? $capacity / $totalAvailable : 1;

        foreach ($resources as $res) {
            $available = min($res->amount, floor($capacity * $multiplier));
            $extracted = floor($available * $ratio);

            $resourceId = $this->resourceService->findResourceByType($res->resource_type)?->id;
            $resourcesExtracted[$resourceId] = $extracted;
            $remainingResources[$res->resource_type] = $res->amount - $extracted;
        }

        return [$resourcesExtracted, $remainingResources];
    }

    public function getSpacecraftsWithDetails($user, Collection $spacecrafts): Collection
    {
        return $user->spacecrafts()
            ->with('details')
            ->whereHas('details', function ($query) use ($spacecrafts) {
                $query->whereIn('name', $spacecrafts->keys());
            })
            ->get();
    }

    public function resolveSpacecraftsAndIds(Collection $spacecrafts, Collection $spacecraftsWithDetails)
    {
        // Mappe Name => details_id
        $nameToId = [];
        foreach ($spacecraftsWithDetails as $sc) {
            $nameToId[$sc->details->name] = $sc->details->id;
        }
    
        // Baue neues Array: [details_id => amount]
        $mapped = collect();
        foreach ($spacecrafts as $name => $amount) {
            if (isset($nameToId[$name])) {
                $mapped[$nameToId[$name]] = $amount;
            }
        }

        return $mapped;
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

        if ($actionType === QueueActionType::ACTION_TYPE_MINING || $actionType === QueueActionType::ACTION_TYPE_SALVAGING) {
            $travelFactor = config('game.core.spacecraft_mining_travel_factor', 1.0);
        } else {
            $travelFactor = config('game.core.spacecraft_combat_travel_factor', 3.5);
        }

        $baseDuration = max(60, round($distance / ($lowestSpeed > 0 ? $lowestSpeed : 1) * $travelFactor));
        $calculatedDuration = $baseDuration / $spacecraft_flight_speed;

        // Mining-/Salvaging-Zeit addieren
        if ($actionType === QueueActionType::ACTION_TYPE_MINING || $actionType === QueueActionType::ACTION_TYPE_SALVAGING) {
            $calculatedDuration += $this->applyOperationSpeedByActionType(
                $target,
                $actionType,
                $spacecrafts,
                $filteredSpacecrafts
            );
        }

        return $calculatedDuration;
    }

    /**
     * Berechnet die aktionsspezifische Operationsdauer und gibt sie zurück
     */
    private function applyOperationSpeedByActionType(
        Asteroid|Station|Rebel $target,
        QueueActionType $actionType,
        Collection $spacecrafts,
        ?Collection $filteredSpacecrafts = null
    ): int {
        if ($actionType === QueueActionType::ACTION_TYPE_MINING && $target instanceof Asteroid) {
            $effectiveOperationSpeed = $this->applyDiminishingReturns($spacecrafts, $filteredSpacecrafts);

            $operationValue = $this->getOperationValueByAsteroid($target);
            $miningTime = round(($operationValue / max(1, $effectiveOperationSpeed)) * 60); // Zeit in Sekunden

            return max(10, $miningTime);
        } elseif ($actionType === QueueActionType::ACTION_TYPE_SALVAGING) {
            $effectiveOperationSpeed = $this->applyDiminishingReturns($spacecrafts, $filteredSpacecrafts);

            $baseSalvagingValue = 420;
            $salvagingTime = round($baseSalvagingValue / $effectiveOperationSpeed);

            return max(10, $salvagingTime);
        }

        return 0;
    }

    /**
     * Wendet Diminishing Returns pro Miner an (formelbasiert: erster zählt voll, jeder weitere mit 0.85^n)
     */
    private function applyDiminishingReturns(Collection $spacecrafts, ?Collection $filteredSpacecrafts = null): float
    {
        // Miner-Schiffe und deren Anzahl sammeln
        $miners = [];
        foreach ($spacecrafts as $spacecraft) {
            if (isset($spacecraft->details) && $spacecraft->details->type === 'Miner') {
                $count = 1;
                if ($filteredSpacecrafts && $filteredSpacecrafts->has($spacecraft->details->name)) {
                    $count = $filteredSpacecrafts[$spacecraft->details->name];
                }
                for ($i = 0; $i < $count; $i++) {
                    $miners[] = $spacecraft->operation_speed ?? 1;
                }
            }
        }
    
        // Sortiere Miner nach operation_speed (optional)
        rsort($miners);
    
        $effectiveSpeed = 0;
        foreach ($miners as $i => $speed) {
            $factor = pow(0.85, $i); // 1.0, 0.85, 0.85^2, 0.85^3, ...
            $effectiveSpeed += $speed * $factor;
        }
    
        return max(1, round($effectiveSpeed, 2));
    }

    /**
     * Basiswert der OperationValue je nach Asteroidgröße
     */
    private function getOperationValueByAsteroid(Asteroid $asteroid): int
    {
        return match ($asteroid->size) {
            'small'   => 300,   // z.B. 300 Einheiten
            'medium'  => 600,
            'large'   => 1200,
            'extreme' => 2400,
            default   => 300,
        };
    }

    /**
     * Findet die niedrigste Geschwindigkeit in einer Sammlung von Raumschiffen
     */
    private function findLowestSpeedOfSpacecrafts(Collection $spacecrafts): int
    {
        return $spacecrafts->pluck('speed')->filter()->min() ?? 1;
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
}
