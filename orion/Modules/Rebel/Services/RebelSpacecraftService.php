<?php

namespace Orion\Modules\Rebel\Services;

use Illuminate\Support\Facades\Log;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Rebel\Models\RebelSpacecraft;
use Orion\Modules\Rebel\Services\RebelResourceService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class RebelSpacecraftService
{
    public function __construct(
        private readonly SpacecraftService $spacecraftService,
        private readonly RebelResourceService $rebelResourceService,
    ) {
    }

    public function spendResourcesForFleet(Rebel $rebel)
    {
        $phase = $this->rebelResourceService->getGamePhase();
        $factionShips = $this->getFactionSpacecrafts($rebel->faction, $phase);

        $behaviors = config('game.rebels.behaviors');
        $behaviorKey = $rebel->behavior;
        $behavior = $behaviors[$behaviorKey] ?? $behaviors['balanced'];
        $fleetBias = $behavior['fleet_bias'];

        $totalResources = $rebel->resources->sum('amount');

        // 1. Reserve abhängig vom Difficulty-Level (Punkt 1 + 3)
        $reservePercent = match ($rebel->difficulty_level) {
            1 => 0.50,  // 75% übrig
            2 => 0.40,
            3 => 0.30,
            4 => 0.25,
            5 => 0.20,
            default => max(0.3 - ($rebel->difficulty_level * 0.02), 0.15), // Skalierbar >5
        };

        // Zufälliger Faktor, damit es nicht immer identisch ist
        $reserveFactor = $reservePercent + rand(0, 5) / 100; 

        $resourceBudget = $totalResources * (1 - $reserveFactor);

        // Durchschnittliche Schiffskosten
        $avgShipCost = collect($factionShips)->map(fn($ship) =>
            collect($ship['costs'])->sum('amount')
        )->avg() ?: 1;

        $fleetCap = $rebel->fleet_cap;

        $totalShips = max(2, min(floor($resourceBudget / $avgShipCost), $fleetCap));

        // Flottenplanung (Bias berücksichtigen)
        $plannedFleet = [
            'attack' => round($fleetBias['attack'] * $totalShips),
            'defense' => round($fleetBias['defense'] * $totalShips),
        ];

        [$attackShips, $defenseShips, $balancedShips] = $this->categorizeShips($factionShips);

        Log::info("Rebel {$rebel->name} {$rebel->behavior} planning: " . json_encode($plannedFleet));

        // 2. Geplante Flotte bauen (mit Effizienz abhängig von Difficulty, Punkt 5)
        $this->buildCategoryShips($rebel, $attackShips, $plannedFleet['attack'], $fleetCap, $rebel->difficulty_level);
        $this->buildCategoryShips($rebel, $defenseShips, $plannedFleet['defense'], $fleetCap, $rebel->difficulty_level);
        $this->buildCategoryShips($rebel, $balancedShips, rand(0, floor($totalShips * 0.1)), $fleetCap, $rebel->difficulty_level);
    }

    private function calculateBuildCount(int $difficulty, int $targetCount, int $maxBuild): int
    {
        // Difficulty 1 = ineffizient, Difficulty 5 = effizient
        $efficiency = min(1.0, 0.5 + ($difficulty * 0.1)); // 0.6 → 1.0

        $idealBuild = floor($maxBuild * $efficiency);
        return max(1, min($targetCount, $idealBuild));
    }

    private function buildCategoryShips(Rebel $rebel, array $ships, int $targetCount, int $fleetCap, int $difficulty)
    {
        if ($targetCount <= 0 || empty($ships)) {
            return;
        }

        while ($targetCount > 0) {
            $ship = $ships[array_rand($ships)];

            $maxBuild = $fleetCap - RebelSpacecraft::where('rebel_id', $rebel->id)->sum('count');
            foreach ($ship['costs'] as $cost) {
                $resource = $this->rebelResourceService->getRebelResource($rebel, $cost);
                $possible = $resource ? floor($resource->amount / $cost['amount']) : 0;
                $maxBuild = min($maxBuild, $possible);
            }

            if ($maxBuild < 1) {
                // statt sofort abbrechen → günstige Alternative probieren
                $fallbackShip = $this->findCheaperAlternative($ships, $rebel);
                if (!$fallbackShip) {
                    break; // wirklich nichts baubar
                }
                $ship = $fallbackShip;
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

            $targetCount -= $buildCount;
        }
    }

    private function findCheaperAlternative(array $ships, Rebel $rebel): ?array
    {
        // Sortiere nach Kosten
        $sorted = collect($ships)->sortBy(fn($s) =>
            collect($s['costs'])->sum('amount')
        );

        foreach ($sorted as $ship) {
            $canAfford = true;
            foreach ($ship['costs'] as $cost) {
                $res = $this->rebelResourceService->getRebelResource($rebel, $cost);
                if (!$res || $res->amount < $cost['amount']) {
                    $canAfford = false;
                    break;
                }
            }
            if ($canAfford) {
                return $ship;
            }
        }

        return null; // keine Alternative gefunden
    }

    private function categorizeShips(array $ships): array
    {
        $attackShips = [];
        $defenseShips = [];
        $balancedShips = [];

        foreach ($ships as $ship) {
            $attack = $ship['attack'] ?? 0;
            $defense = $ship['defense'] ?? 0;

            if ($attack > $defense * 1.15) {
                $attackShips[] = $ship;
            } elseif ($defense > $attack * 1.15) {
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
        
