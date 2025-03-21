<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Models\Building;

class BuildingCostCalculator
{
    public function calculateUpgradeCost(Building $building)
    {
        $buildingName = $building->details->name;
        $nextLevel = $building->level + 1;
        
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
            $levelMultiplier = pow($growthFactor, $nextLevel - 1);
            
            // Meilenstein-Multiplikator anwenden
            $milestoneMultiplier = $this->getMilestoneMultiplier($nextLevel - 1);
            
            // Endgültige Menge berechnen
            $amount = ceil($baseAmount * $levelMultiplier * $milestoneMultiplier);
            
            $costs[] = [
                'resource_name' => $resourceName,
                'amount' => $amount,
            ];
        }
        
        // Neue Ressourcen hinzufügen, wenn Level-Schwellenwert erreicht
        $additionalResources = $this->getAdditionalResources($buildingName, $nextLevel);
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
    
    private function getAdditionalResources($buildingName, $level)
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
                    $baseAmount = 20 + ($level - $levelThreshold) * 5;
                    
                    // Wert der Ressource berücksichtigen
                    $resourceValue = $this->getResourceValue($resourceName);
                    $amount = ceil($baseAmount / ($resourceValue / 150));
                    
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
