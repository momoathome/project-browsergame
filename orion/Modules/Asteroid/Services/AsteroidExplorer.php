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

    /**
     * Liefert zu einer [name => amount]-Liste:
     * - die passenden Spacecraft-Modelle mit Details
     * - das Mapping [details_id => amount]
     */
    public function resolveSpacecraftsAndIds(Collection $spacecrafts, Collection $spacecraftsWithDetails): array
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
    
        return [
            'spacecraftsWithDetails' => $spacecraftsWithDetails,
            'spacecraftsById' => $mapped,
        ];
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
}
