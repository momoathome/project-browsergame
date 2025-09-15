<?php
namespace Orion\Modules\Building\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\Building\Enums\BuildingEffectType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Resource\Services\ResourceService;

class BuildingProgressionService
{

    public function __construct(
        private readonly ResourceService $resourceService
    ) {
    }

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
        return 1.0; // Kein Meilenstein-Multiplikator
    }
    
    /**
     * Berechnet den Effektwert eines Gebäudes basierend auf seinem Typ und Level
     */
    public function calculateEffectValue(Building $building): float
    {
        $buildingType = BuildingType::tryFrom($building->details->name);

        if (!$buildingType) {
            // Fallback für unbekannte Gebäudetypen
            return $building->effect_value * 1.1;
        }

        $config = $buildingType->getEffectConfiguration();
        $effectType = BuildingEffectType::tryFrom($config['type'] ?? 'additive') ?? BuildingEffectType::ADDITIVE;
        $baseValue = $config['base_value'] ?? 1.0;
        $increment = $config['increment'] ?? 0.1;
        $level = $building->level;

        // Spezialfall Laboratory: immer nur das Inkrement pro Upgrade
        if ($buildingType === BuildingType::LABORATORY) {
            return $building->effect_value + $increment;
        }

        return match ($effectType) {
            BuildingEffectType::ADDITIVE => $this->calculateAdditiveEffect($baseValue, $increment, $level),
            BuildingEffectType::MULTIPLICATIVE => $this->calculateMultiplicativeEffect($baseValue, $increment, $level),
            BuildingEffectType::EXPONENTIAL => $this->calculateExponentialEffect($baseValue, $increment, $level),
            BuildingEffectType::LOGARITHMIC => $this->calculateLogarithmicEffect($baseValue, $increment, $level),
            default => $baseValue + ($level - 1) * $increment,
        };
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
        // Berechne Level-Wachstum mit Exponentialfunktion
        $levelMultiplier = pow($growthFactor, $targetLevel - 2); // -2, da Level 1 die Basis ist und (Upgrade von 1 auf 2): Exponent = 0 
        $milestoneMultiplier = $this->getMilestoneMultiplier($targetLevel);

        // Kosten entsprechend des Levels berechnen
        $costs = [];
        foreach ($baseCosts as $baseCost) {
            $resourceName = $baseCost['resource_name'];
            $baseAmount = $baseCost['amount'];
            $resourceId = $this->resourceService->getResourceIdByName($resourceName);

            if ($targetLevel === 2) {
                $amount = $baseAmount;
            } else {
                // Endgültige Menge berechnen
                $amount = ceil($baseAmount * $levelMultiplier * $milestoneMultiplier);
            }

            $costs[$resourceId] = [
                'id' => $resourceId,
                'name' => $resourceName,
                'amount' => (int) $amount,
            ];
        }

        // Neue Ressourcen hinzufügen, wenn Level-Schwellenwert erreicht
        $additionalResources = $this->getAdditionalResources($buildingName, $targetLevel - 1, $levelMultiplier, $milestoneMultiplier);
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
        // Bauzeit je nach Level erhöhen
        $baseConfig = $this->getBaseConfig($building->details->name);
        $buildTimeMultiplier = config('game.building_progression.build_time_multiplier', 1.25);
        if ($targetLevel === 2) {
            $buildTime = $baseConfig['build_time'];
        } else {
            $buildTime = floor($baseConfig['build_time'] * pow($buildTimeMultiplier, $targetLevel - 2));
        }
        return $buildTime;
    }

    /**
     * Berechnet einen additiven Effekt: Basiswert + (Level-1) * Inkrement
     */
    private function calculateAdditiveEffect(float $baseValue, float $increment, int $level): float
    {
        return $baseValue + ($level - 1) * $increment;
    }

    /**
     * Berechnet einen multiplikativen Effekt: Basiswert * (1 + (Level-1) * Inkrement)
     */
    private function calculateMultiplicativeEffect(float $baseValue, float $increment, int $level): float
    {
        return $baseValue * (1 + ($level - 1) * $increment);
    }

    /**
     * Berechnet einen exponentiellen Effekt: Basiswert * (Inkrement ^ (Level-1))
     */
    private function calculateExponentialEffect(float $baseValue, float $increment, int $level): float
    {
        return $baseValue * pow($increment, $level - 1);
    }

    /**
     * Berechnet einen logarithmischen Effekt: Basiswert * (1 + log(Level) * Inkrement)
     */
    private function calculateLogarithmicEffect(float $baseValue, float $increment, int $level): float
    {
        return $baseValue * (1 + log($level) * $increment);
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

    public function getEffectPreview(Building $building, bool $nextLevel = false): array
    {
        $buildingType = BuildingType::tryFrom($building->details->name);
        if (!$buildingType) {
            return [];
        }
    
        $level = $nextLevel ? $building->level + 1 : $building->level;
        $config = $buildingType->getEffectConfiguration();
        $effectType = BuildingEffectType::tryFrom($config['type'] ?? 'additive') ?? BuildingEffectType::ADDITIVE;
        $baseValue = $config['base_value'] ?? 1.0;
        $increment = $config['increment'] ?? 0.1;
    
        // Aktueller oder zukünftiger Effektwert
        // Spezialfall Laboratory: immer nur das Inkrement pro Upgrade + den aktuellen wert

        $effectValue = ($buildingType === BuildingType::LABORATORY)
            ? $increment
            : match ($effectType) {
                BuildingEffectType::ADDITIVE => $this->calculateAdditiveEffect($baseValue, $increment, $level),
                BuildingEffectType::MULTIPLICATIVE => $this->calculateMultiplicativeEffect($baseValue, $increment, $level),
                BuildingEffectType::EXPONENTIAL => $this->calculateExponentialEffect($baseValue, $increment, $level),
                BuildingEffectType::LOGARITHMIC => $this->calculateLogarithmicEffect($baseValue, $increment, $level),
                default => $baseValue + ($level - 1) * $increment,
            };
    
        // Rückgabe mit formatiertem Wert und Beschreibung
        $attributes = $buildingType->getEffectAttributes();
        $results = [];
    
        foreach ($attributes as $attributeName) {
            $flooredEffectValue = floor($effectValue);
            $formattedValue = number_format($effectValue, 2, ',', '.');
            $formattedValueNoDecimals = number_format($flooredEffectValue, 0, ',', '.');
            $formattedPercent = number_format(($effectValue - 1) * 100, 0, ',', '.') . "%";
        
            // Enum verwenden für Mapping
            $effectText = match ($attributeName) {
                UserAttributeType::UPGRADE_SPEED->value => "Upgrade speed",
                UserAttributeType::PRODUCTION_SPEED->value => "Production speed",
                UserAttributeType::BASE_DEFENSE->value => "Defense",
                UserAttributeType::STORAGE->value => "Resource storage",
                UserAttributeType::SCAN_RANGE->value => "Scanner range",
                UserAttributeType::CREW_LIMIT->value => "Crew Limit",
                UserAttributeType::RESEARCH_POINTS->value => "Research Points",
                default => $attributeName
            };
        
            $effectValue = match ($attributeName) {
                UserAttributeType::UPGRADE_SPEED->value,
                UserAttributeType::PRODUCTION_SPEED->value,
                UserAttributeType::BASE_DEFENSE->value => $formattedPercent,
                UserAttributeType::STORAGE->value,
                UserAttributeType::SCAN_RANGE->value,
                UserAttributeType::CREW_LIMIT->value,
                UserAttributeType::RESEARCH_POINTS->value => $formattedValueNoDecimals,
                default => $formattedValue
            };
        
            $results[] = [
                'effect' => [
                    'text' => $effectText,
                    'value' => $effectValue
                ]
            ];
        }
    
        return $results;
    }

}
