<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Models\Building;

class BuildingCostCalculator
{
    public function calculateUpgradeCost(Building $building)
    {
        $buildingName = $building->details->name;
        $level = $building->level;

        $baseConfig = $this->getBaseConfig($buildingName);
        if (!$baseConfig) {
            return [];
        }

        $baseCosts = $baseConfig['costs'];

        $growthFactor = config('game.building_progression.growth_factors.' . $buildingName, 1.35);

        // Kosten entsprechend des Levels berechnen
        $costs = [];
        foreach ($baseCosts as $baseCost) {
            $resourceName = $baseCost['resource_name'];
            $baseAmount = $baseCost['amount'];

            // Berechne Level-Wachstum mit Exponentialfunktion
            $levelMultiplier = pow($growthFactor, $level);

            // Meilenstein-Multiplikator anwenden
            $milestoneMultiplier = $this->getMilestoneMultiplier($level);

            // Endgültige Menge berechnen
            $amount = ceil($baseAmount * $levelMultiplier * $milestoneMultiplier);

            $costs[] = [
                'resource_name' => $resourceName,
                'amount' => $amount,
            ];
        }

        // Neue Ressourcen hinzufügen, wenn Level-Schwellenwert erreicht
        $additionalResources = $this->getAdditionalResources($buildingName, $level + 1, $levelMultiplier, $milestoneMultiplier);
        foreach ($additionalResources as $resourceName => $amount) {
            $costs[] = [
                'resource_name' => $resourceName,
                'amount' => $amount,
            ];
        }

        return $costs;
    }

    private function getBaseConfig($buildingName)
    {
        $buildings = config('game.buildings.buildings');
        foreach ($buildings as $building) {
            if ($building['name'] === $buildingName) {
                return $building;
            }
        }
        return null;
    }

    private function getMilestoneMultiplier($level)
    {
        $milestones = config('game.building_progression.milestone_multipliers', []);
        foreach ($milestones as $milestoneLevel => $multiplier) {
            if ($level == $milestoneLevel) {
                return $multiplier;
            }
        }
        return 1.0; // Kein Meilenstein-Multiplikator
    }

    private function getAdditionalResources($buildingName, $level, $levelMultiplier, $milestoneMultiplier)
    {
        $additionalResources = [];

        // Konfiguration für dieses Gebäude laden
        $buildingResources = config('game.building_progression.building_resources.' . $buildingName, []);

        // Prüfen, welche neuen Ressourcen bei diesem Level hinzugefügt werden sollten
        foreach ($buildingResources as $levelKey => $resources) {
            if ($levelKey === 'base') {
                continue; // Basisressourcen überspringen
            }

            // Extrahiere Level-Zahl aus dem Key (z.B. "level_10" -> 10)
            $levelThreshold = (int) str_replace('level_', '', $levelKey);

            if ($level >= $levelThreshold) {
                foreach ($resources as $resourceName) {
                    // Grundmenge für neue Ressource berechnen
                    // Die Formel kann angepasst werden, um den Schwierigkeitsgrad zu steuern
                    $baseValue = config('game.building_progression.additional_resource_base_value');
                    $additionalResourcesMultiplier = config('game.building_progression.additional_resources_multiplier', 1);
                    $baseAmount = $baseValue + ($level - $levelThreshold) * $additionalResourcesMultiplier;

                    // Wert der Ressource berücksichtigen - umgekehrter Zusammenhang
                    $resourceValue = $this->getResourceValue($resourceName);
                    $referenceValue = config('game.building_progression.additional_resource_referenz', 1000); // Referenzwert für die Berechnung

                    // Je niedriger der Wert, desto mehr wird benötigt
                    $valueRatio = $referenceValue / $resourceValue;
                    $amount = ceil($baseAmount * $valueRatio * $levelMultiplier * $milestoneMultiplier);

                    $additionalResources[$resourceName] = $amount;
                }
            }
        }

        return $additionalResources;
    }

    private function getResourceValue($resourceName)
    {
        $markets = config('game.market.markets');
        foreach ($markets as $market) {
            if ($market['resource_name'] === $resourceName) {
                return $market['cost'];
            }
        }
        return 150; // Default-Wert
    }
}
