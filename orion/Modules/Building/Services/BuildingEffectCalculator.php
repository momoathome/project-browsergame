<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Enums\BuildingEffectType;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\Building\Models\Building;

class BuildingEffectCalculator
{
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
        $effectType = $config['type'] ?? BuildingEffectType::MULTIPLICATIVE;
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

    /**
     * Berechnet einen additiven Effekt: Basiswert + (Level-1) * Inkrement
     */
    private function calculateAdditiveEffect(float $baseValue, float $increment, int $level): float
    {
        return floor($baseValue + ($level - 1) * $increment);
    }

    /**
     * Berechnet einen multiplikativen Effekt: Basiswert * (1 + (Level-1) * Inkrement)
     */
    private function calculateMultiplicativeEffect(float $baseValue, float $increment, int $level): float
    {
        return floor($baseValue * (1 + ($level - 1) * $increment));
    }

    /**
     * Berechnet einen exponentiellen Effekt: Basiswert * (Inkrement ^ (Level-1))
     */
    private function calculateExponentialEffect(float $baseValue, float $increment, int $level): float
    {
        return floor($baseValue * pow($increment, $level - 1));
    }

    /**
     * Berechnet einen logarithmischen Effekt: Basiswert * (1 + log(Level) * Inkrement)
     */
    private function calculateLogarithmicEffect(float $baseValue, float $increment, int $level): float
    {
        return floor($baseValue * (1 + log($level) * $increment));
    }

    // Methode zum Anzeigen von Gebäudeeffekten
    public function getEffectPreview(Building $building, bool $nextLevel = false): array
    {
        $buildingType = BuildingType::tryFrom($building->details->name);
        if (!$buildingType) {
            return [];
        }
    
        $level = $nextLevel ? $building->level + 1 : $building->level;
        $config = $buildingType->getEffectConfiguration();
        $baseValue = $config['base_value'] ?? 1.0;
        $increment = $config['increment'] ?? 0.1;
        $effectType = $config['type'] ?? BuildingEffectType::MULTIPLICATIVE;
    
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
            $formattedValue = number_format($effectValue, 2, ',', '.');
            $formattedValueNoDecimals = number_format($effectValue, 0, ',', '.');
            $formattedPercent = number_format(($effectValue - 1) * 100, 0, ',', '.') . "%";

            $displayText = match ($attributeName) {
                'production_speed' => "{$formattedPercent} Production speed",
                'base_defense' => "{$formattedPercent} Defense",
                'storage' => "{$formattedValueNoDecimals} Resource storage",
                'scan_range' => "{$formattedValueNoDecimals} Scanner range",
                'crew_limit' => "{$formattedValueNoDecimals} Crew Limit",
                'research_points' => "+{$formattedValueNoDecimals} Research Points",
                // 'energy_output' => "+{$effectValue} energy output",
                // 'trade_income' => "+{$effectValue} Trade Limit",
                default => "+{$effectValue} {$attributeName}"
            };

            $results[] = [
                'attribute' => $attributeName,
                'value' => $effectValue,
                'display' => $displayText
            ];
        }
    
        return $results;
    }
}
