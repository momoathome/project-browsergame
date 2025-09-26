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

        // Hole erlaubte Schiffe für diese Fraktion & Phase
        $factionShips = $this->getFactionSpacecrafts($rebel->faction, $phase);

        // Hole Verhalten → erst individuell, dann Fallback Fraktion
        $behaviors = config('game.rebels.behaviors');
        $behaviorKey = $rebel->behavior;
        $behavior = $behaviors[$behaviorKey] ?? $behaviors['balanced'];

        // Bias bestimmen (z. B. 90/10 für very aggressive)
        $fleetBias = $behavior['fleet_bias'];

        // Gesamtanzahl Schiffe, die er bauen will
        $totalShips = rand(5, 20) * $rebel->difficulty_level;

        $plannedFleet = [
            'attack' => round($fleetBias['attack'] * $totalShips),
            'defense' => round($fleetBias['defense'] * $totalShips),
        ];

        // Kategorisiere Schiffe
        [$attackShips, $defenseShips, $balancedShips] = $this->categorizeShips($factionShips);
        Log::info("Rebel {$rebel->name} {$rebel->behavior} building fleet: " . json_encode($plannedFleet) .
            " from " . count($attackShips) . " attack, " . count($defenseShips) . " defense, " . count($balancedShips) . " balanced ships.");

        // Baue Attack-Schiffe
        $this->buildCategoryShips($rebel, $attackShips, $plannedFleet['attack']);

        // Baue Defense-Schiffe
        $this->buildCategoryShips($rebel, $defenseShips, $plannedFleet['defense']);

        // Optional: Balanced-Schiffe
        $this->buildCategoryShips($rebel, $balancedShips, rand(0, floor($totalShips * 0.1)));
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


    private function buildCategoryShips(Rebel $rebel, array $ships, int $targetCount)
    {
        if ($targetCount <= 0 || empty($ships)) {
            return;
        }

        while ($targetCount > 0) {
            // Zufälliges Schiff aus der Kategorie wählen
            $ship = $ships[array_rand($ships)];

            // Prüfe, wie viele maximal baubar sind
            $maxBuild = PHP_INT_MAX;
            foreach ($ship['costs'] as $cost) {
                $resource = $this->rebelResourceService->getRebelResource($rebel, $cost);
                $possible = $resource ? floor($resource->amount / $cost['amount']) : 0;
                $maxBuild = min($maxBuild, $possible);
            }

            if ($maxBuild < 1) {
                $targetCount--; // Kein Bau möglich → skip
                continue;
            }

            $buildCount = min(rand(1, 3), $maxBuild, $targetCount); // Variation
            $this->rebelResourceService->spendResources($rebel, $ship['costs'], $buildCount);

            // Erstelle oder update
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

        // Hilfsfunktion: Hole alle Spacecrafts für die Fraktion
    public function getFactionSpacecrafts($faction, $phase)
    {
        $factionConfig = config('game.rebels.faction_spacecrafts');
        $allowedNames = $factionConfig[$faction][$phase] ?? [];

        $allShips = config('game.spacecrafts.spacecrafts');
        return array_filter($allShips, fn($ship) => in_array($ship['name'], $allowedNames));
    }
}
        
