<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Services\BuildingEffectService;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Resource\Services\ResourceService;

class BuildingProgressionService
{
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly BuildingEffectService $effectService
    ) {}

    public function getBaseConfig($buildingName)
    {
        $buildings = config('game.buildings.buildings');
        foreach ($buildings as $building) {
            if ($building['name'] === $buildingName) {
                return $building;
            }
        }
        return null;
    }

    public function getMilestoneMultiplier($level)
    {
        $milestones = config('game.building_progression.milestone_multipliers', []);
        foreach ($milestones as $milestoneLevel => $multiplier) {
            if ($level == $milestoneLevel) {
                return $multiplier;
            }
        }
        return 1.0;
    }

    /**
     * ⬅️ NEU: Holt Effekt-Werte über den BuildingEffectService
     */
    public function calculateEffectValue(Building $building): array
    {
        return $this->effectService->getEffects(
            $building->details->name,
            $building->level
        );
    }

    /**
     * Holt NUR die Basis-Effekte für das Gebäude (für effect_value).
     */
    public function calculateBaseEffectValue(Building $building): array
    {
        return $this->effectService->getBaseEffects(
            $building->details->name,
            $building->level
        );
    }

    public function calculateUpgradeCost(Building $building , int $targetLevel)
    {
        $buildingName = $building->details->name;
        $baseConfig = $this->getBaseConfig($buildingName);
        if (!$baseConfig) {
            return [];
        }

        $baseCosts = $baseConfig['costs'];
        $growthFactor = config('game.building_progression.growth_factors.' . $buildingName, 1.35);

        $levelMultiplier = pow($growthFactor, $targetLevel - 2);
        $milestoneMultiplier = $this->getMilestoneMultiplier($targetLevel);

        $costs = [];
        foreach ($baseCosts as $baseCost) {
            $resourceName = $baseCost['resource_name'];
            $baseAmount = $baseCost['amount'];
            $resourceId = $this->resourceService->getResourceIdByName($resourceName);

            $amount = ($targetLevel === 2)
                ? $baseAmount
                : ceil($baseAmount * $levelMultiplier * $milestoneMultiplier);

            $costs[$resourceId] = [
                'id' => $resourceId,
                'name' => $resourceName,
                'amount' => (int) $amount,
            ];
        }

        // Zusätzliche Ressourcen
        $additionalResources = $this->getAdditionalResources(
            $buildingName,
            $targetLevel - 1,
            $levelMultiplier,
            $milestoneMultiplier
        );

        foreach ($additionalResources as $resourceName => $amount) {
            $resourceId = $this->resourceService->getResourceIdByName($resourceName);
            $costs[$resourceId] = [
                'id'=> $resourceId,
                'name' => $resourceName,
                'amount' => (int) $amount,
            ];
        }

        return $costs;
    }

    public function calculateBuildTime(Building $building, int $targetLevel): float
    {
        $baseConfig = $this->getBaseConfig($building->details->name);
        $buildTimeMultiplier = config('game.building_progression.build_time_multiplier', 1.25);

        return ($targetLevel === 2)
            ? $baseConfig['build_time']
            : floor($baseConfig['build_time'] * pow($buildTimeMultiplier, $targetLevel - 2));
    }

    private function getAdditionalResources($buildingName, $level, $levelMultiplier, $milestoneMultiplier)
    {
        $additionalResources = [];
        $buildingResources = config('game.building_progression.building_resources.' . $buildingName, []);

        foreach ($buildingResources as $levelKey => $resources) {
            if ($levelKey === 'base') continue;

            $levelThreshold = (int) str_replace('level_', '', $levelKey);

            if ($level >= $levelThreshold) {
                foreach ($resources as $resourceName) {
                    $baseValue = config('game.building_progression.additional_resource_base_value');
                    $additionalResourcesMultiplier = config('game.building_progression.additional_resources_multiplier', 1);

                    // Basiswert pro Level berechnen
                    $baseAmount = $baseValue + ($level - $levelThreshold) * $additionalResourcesMultiplier;

                    // NEU: Kategorie-Wert statt Einzelkosten
                    $resourceCategoryValue = $this->getResourceCategoryValue($resourceName);

                    // Referenzwert wie bisher
                    $referenceValue = config('game.building_progression.additional_resource_referenz', 1000);

                    // Wert-Ratio basierend auf Kategorie
                    $valueRatio = $referenceValue / $resourceCategoryValue;

                    // Menge berechnen
                    $amount = ceil($baseAmount * $valueRatio * $levelMultiplier * $milestoneMultiplier);

                    $additionalResources[$resourceName] = $amount;
                }
            }
        }

        return $additionalResources;
    }

    private function getResourceCategoryValue(string $resourceName): int
    {
        $markets = config('game.market.markets');
        $marketCategoryValues = config('game.market.market_category_values', []);

        foreach ($markets as $market) {
            if ($market['resource_name'] === $resourceName) {
                $category = $market['category'] ?? 'low_value';
                return $marketCategoryValues[$category] ?? $marketCategoryValues['low_value'];
            }
        }

        // Fallback, wenn Ressource nicht in Markets gefunden wird
        return $marketCategoryValues['low_value'] ?? 1;
    }

    /**
     * ⬅️ NEU: Vorschau für UI, nutzt ebenfalls BuildingEffectService
     */
    public function getEffectPreview(Building $building, bool $nextLevel = false): array
    {
        $level = $nextLevel ? $building->level + 1 : $building->level;
    
        $effects = $this->effectService->getEffects(
            $building->details->name,
            $level
        );
    
        // Keys, die als Prozent angezeigt werden sollen
        $percentKeys = ['upgrade_speed', 'production_speed', 'base_defense'];
    
        foreach ($effects as $key => $value) {
            if (in_array($key, $percentKeys, true) && is_numeric($value)) {
                $effects[$key] = round(($value - 1) * 100, 2) . '%';
            }
        }
    
        return $effects;
    }
}
