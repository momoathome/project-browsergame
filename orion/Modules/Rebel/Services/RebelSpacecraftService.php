<?php

namespace Orion\Modules\Rebel\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Rebel\Models\RebelResource;
use Orion\Modules\Rebel\Models\RebelSpacecraft;
use Orion\Modules\Rebel\Services\RebelResourceService;
use Orion\Modules\Rebel\Services\RebelDifficultyService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class RebelSpacecraftService
{
    public function __construct(
        private readonly SpacecraftService $spacecraftService,
        private readonly RebelResourceService $rebelResourceService,
        private readonly RebelDifficultyService $difficultyService,
    ) {
    }

    public function spendResourcesForFleet(Rebel $rebel, ?float $globalDifficulty = null): void
    {
        $globalDifficulty = $globalDifficulty ?? $this->difficultyService->calculateGlobalDifficulty();
        $phase = $this->rebelResourceService->getGamePhase();
        $difficulty = $rebel->difficulty_level + $globalDifficulty;

        // --- PRELOAD ALL RESOURCES & SPACECRAFTS ---
        $resources = RebelResource::where('rebel_id', $rebel->id)->get()->keyBy('resource_id');
        $spacecrafts = RebelSpacecraft::where('rebel_id', $rebel->id)->get()->keyBy('details_id');

        $factionShips = $this->getFactionSpacecrafts($rebel->faction, $phase);
        $behaviors = config('game.rebels.behaviors');
        $behaviorKey = $rebel->behavior;
        $behavior = $behaviors[$behaviorKey] ?? $behaviors['balanced'];
        $fleetBias = $behavior['fleet_bias'];

        $totalResources = $resources->sum('amount');
        $reservePercent = max(0.30, 0.70 - 0.07 * $difficulty);
        $reserveFactor = $reservePercent + rand(0, 5) / 100;
        $resourceBudget = $totalResources * (1 - $reserveFactor);

        $avgShipCost = collect($factionShips)->map(fn($ship) =>
            collect($ship['costs'])->sum('amount')
        )->avg() ?: 1;

        $fleetCap = $this->difficultyService->getFleetCap($rebel, $globalDifficulty);
        $totalShips = max(2, min(floor($resourceBudget / $avgShipCost), $fleetCap));

        [$attackShips, $defenseShips, $balancedShips] = $this->categorizeShips($factionShips);
        $plannedFleet = $this->adjustPlannedFleet($fleetBias, $totalShips, $attackShips, $defenseShips);

        // --- Build Ships in a TRANSACTION ---
        DB::transaction(function () use (
            $rebel, $plannedFleet, $attackShips, $defenseShips, $balancedShips, $fleetCap, $resources, $spacecrafts, $difficulty
        ) {
            $this->buildCategoryShips($rebel, $attackShips, $plannedFleet['attack'], $fleetCap, $resources, $spacecrafts, $difficulty);
            $this->buildCategoryShips($rebel, $defenseShips, $plannedFleet['defense'], $fleetCap, $resources, $spacecrafts, $difficulty);
            $this->buildCategoryShips($rebel, $balancedShips, $plannedFleet['balanced'], $fleetCap, $resources, $spacecrafts, $difficulty);
        });
    }

    /**
     * Optimierte Schiffsbau-Methode mit Preloaded Resources/Spacecrafts
     */
    private function buildCategoryShips(Rebel $rebel, array $ships, int $targetCount, int $fleetCap, $resources, $spacecrafts, $difficulty): void
    {
        if ($targetCount <= 0 || empty($ships)) return;

        $currentFleetSize = $spacecrafts->sum('count');
        $iterations = 0;

        while ($targetCount > 0 && $iterations++ < 200) {
            $ship = $ships[array_rand($ships)];
            $maxBuild = $fleetCap - $currentFleetSize;

            foreach ($ship['costs'] as $cost) {
                $resource = $resources[$this->rebelResourceService->getResourceId($cost['resource_name'])] ?? null;
                $possible = $resource ? floor($resource->amount / $cost['amount']) : 0;
                $maxBuild = min($maxBuild, $possible);
            }

            if ($maxBuild < 1) {
                $targetCount--;
                continue;
            }

            $buildCount = min(
                $this->calculateBuildCount($difficulty, $targetCount, $maxBuild),
                $targetCount
            );
            // Ressourcen abziehen
            foreach ($ship['costs'] as $cost) {
                $resourceId = $this->rebelResourceService->getResourceId($cost['resource_name']);
                $resources[$resourceId]->decrement('amount', $cost['amount'] * $buildCount);
            }

            // Schiff hinzufügen
            $spacecraft = $spacecrafts[$ship['details_id']] ?? new RebelSpacecraft([
                'rebel_id' => $rebel->id,
                'details_id' => $ship['details_id'],
                'attack' => $ship['attack'] ?? 0,
                'defense' => $ship['defense'] ?? 0,
                'cargo' => $ship['cargo'] ?? 0,
                'count' => 0,
            ]);

            $spacecraft->count += $buildCount;
            $spacecraft->save();
            $spacecrafts[$ship['details_id']] = $spacecraft;

            $currentFleetSize += $buildCount;
            $targetCount -= $buildCount;
        }
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
        
