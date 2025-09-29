<?php

namespace Orion\Modules\Rebel\Services;

use Illuminate\Support\Facades\Log;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Rebel\Models\RebelSpacecraft;
use Orion\Modules\Rebel\Services\RebelResourceService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Rebel\Services\RebelDifficultyService;

class RebelSpacecraftService
{
    public function __construct(
        private readonly SpacecraftService $spacecraftService,
        private readonly RebelResourceService $rebelResourceService,
        private readonly RebelDifficultyService $difficultyService,
    ) {
    }

    public function spendResourcesForFleet(Rebel $rebel, ?float $globalDifficulty = 0)
    {
        $globalDifficulty = $globalDifficulty ?? $this->difficultyService->calculateGlobalDifficulty();

        $phase = $this->rebelResourceService->getGamePhase();
        $factionShips = $this->getFactionSpacecrafts($rebel->faction, $phase);

        $behaviors = config('game.rebels.behaviors');
        $behaviorKey = $rebel->behavior;
        $behavior = $behaviors[$behaviorKey] ?? $behaviors['balanced'];
        $fleetBias = $behavior['fleet_bias'];

        $totalResources = $rebel->resources->sum('amount');

        $reservePercent = max(0.30, 0.70 - 0.07 * ($rebel->difficulty_level + $globalDifficulty));

        $reserveFactor = $reservePercent + rand(0, 5) / 100;
        $resourceBudget = $totalResources * (1 - $reserveFactor);

        $avgShipCost = collect($factionShips)->map(fn($ship) =>
            collect($ship['costs'])->sum('amount')
        )->avg() ?: 1;

        // FleetCap dynamisch
        $fleetCap = $this->difficultyService->getFleetCap($rebel, $globalDifficulty);

        $totalShips = max(2, min(floor($resourceBudget / $avgShipCost), $fleetCap));

        [$attackShips, $defenseShips, $balancedShips] = $this->categorizeShips($factionShips);
        
        $plannedFleet = $this->adjustPlannedFleet($fleetBias, $totalShips, $attackShips, $defenseShips);

        $this->buildCategoryShips($rebel, $attackShips, $plannedFleet['attack'], $fleetCap, $rebel->difficulty_level);
        $this->buildCategoryShips($rebel, $defenseShips, $plannedFleet['defense'], $fleetCap, $rebel->difficulty_level);
        $this->buildCategoryShips($rebel, $balancedShips, $plannedFleet['balanced'], $fleetCap, $rebel->difficulty_level);
    }

    private function calculateBuildCount(int $difficulty, int $targetCount, int $maxBuild): int
    {
        // Difficulty 1 = ineffizient, Difficulty 5 = effizient
        $efficiency = min(1.0, 0.3 + ($difficulty * 0.1));

        $idealBuild = floor($maxBuild * $efficiency);
        return max(1, min($targetCount, $idealBuild));
    }

    private function adjustPlannedFleet(array $fleetBias, int $totalShips, array $attackShips, array $defenseShips): array
    {
        $attackCount = round($fleetBias['attack'] * $totalShips);
        $defenseCount = round($fleetBias['defense'] * $totalShips);

        // --- Case 1: Keine Defense-Schiffe verfügbar → auf Balanced schieben
        if (empty($defenseShips) && $defenseCount > 0) {
            $balancedExtra = $defenseCount;
            $defenseCount = 0;
            $balancedCount = $balancedExtra;
        }
        // --- Case 2: Keine Attack-Schiffe verfügbar → auf Balanced schieben
        elseif (empty($attackShips) && $attackCount > 0) {
            $balancedExtra = $attackCount;
            $attackCount = 0;
            $balancedCount = $balancedExtra;
        }
        // --- Case 3: Beide Kategorien verfügbar → normaler Bias
        else {
            $balancedCount = round($totalShips - ($attackCount + $defenseCount));
        }

        // Safety: Keine negativen Werte
        $attackCount   = max(0, $attackCount);
        $defenseCount  = max(0, $defenseCount);
        $balancedCount = max(0, $balancedCount);

        return [
            'attack'   => $attackCount,
            'defense'  => $defenseCount,
            'balanced' => $balancedCount,
        ];
    }


    private function buildCategoryShips(Rebel $rebel, array $ships, int $targetCount, int $fleetCap, int $difficulty)
    {
        if ($targetCount <= 0 || empty($ships)) {
            return;
        }

        $maxIterations = 500; // Schutz gegen Endlosschleifen
        $iterations = 0;

        while ($targetCount > 0 && $iterations < $maxIterations) {
            $iterations++;

            $ship = $ships[array_rand($ships)];

            $maxBuild = $fleetCap - RebelSpacecraft::where('rebel_id', $rebel->id)->sum('count');
            foreach ($ship['costs'] as $cost) {
                $resource = $this->rebelResourceService->getRebelResource($rebel, $cost);
                $possible = $resource ? floor($resource->amount / $cost['amount']) : 0;
                $maxBuild = min($maxBuild, $possible);
            }

            if ($maxBuild < 1) {
                // Kein Schiff baubar, Ziel reduzieren um Endlosschleife zu vermeiden
                $targetCount--;
                continue;
            }

            // Effizienz beeinflusst wie „clever“ gebaut wird
            $buildCount = min(
                $this->calculateBuildCount($difficulty, $targetCount, $maxBuild),
                $targetCount
            );

            $this->rebelResourceService->spendResources($rebel, $ship['costs'], $buildCount);

            $spacecraft = RebelSpacecraft::firstOrNew([
                'rebel_id' => $rebel->id,
                'details_id' => $ship['details_id'],
            ]);

            $spacecraft->count = ($spacecraft->count ?? 0) + $buildCount;
            $spacecraft->attack = $ship['attack'] ?? 0;
            $spacecraft->defense = $ship['defense'] ?? 0;
            $spacecraft->cargo = $ship['cargo'] ?? 0;
            $spacecraft->save();

            Log::info("Rebel {$rebel->name} built {$buildCount}x {$ship['name']} (now {$spacecraft->count})");

            $targetCount -= $buildCount;
        }
    }

    private function categorizeShips(array $ships): array
    {
        $attackShips = [];
        $defenseShips = [];
        $balancedShips = [];

        foreach ($ships as $ship) {
            $attack = $ship['attack'] ?? 0;
            $defense = $ship['defense'] ?? 0;

            if ($attack > $defense * 1.25) {
                $attackShips[] = $ship;
            } elseif ($defense > $attack * 1.25) {
                $defenseShips[] = $ship;
            } else {
                $balancedShips[] = $ship;
            }
        }

        return [$attackShips, $defenseShips, $balancedShips];
    }

    // Hilfsfunktion: Hole alle Spacecrafts für die Fraktion
    public function getFactionSpacecrafts($faction, $phase)
    {
        $factionConfig = config('game.rebels.faction_spacecrafts');
        $allowedNames = $factionConfig[$faction][$phase] ?? [];

        $allShips = config('game.spacecrafts.spacecrafts');
        return array_filter($allShips, fn($ship) => in_array($ship['name'], $allowedNames));
    }
}
        
