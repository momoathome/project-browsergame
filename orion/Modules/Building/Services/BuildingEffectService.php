<?php

namespace Orion\Modules\Building\Services;

use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Models\BuildingLevel;

class BuildingEffectService
{
    /**
     * Liefert kombinierte Effekte für ein Gebäude und Level.
     */
    public function getEffects(string $buildingKey, int $level): array
    {
        $effects = $this->calculateBaseEffects($buildingKey, $level);

        // DB-Extras mergen
        $extra = $this->getExtraEffects($buildingKey, $level);
        if ($extra) {
            $effects = array_merge($effects, $extra);
        }

        return $effects;
    }

    /**
     * Liefert Effekte für alle Level von 1 bis $maxLevel.
     */
    public function getEffectsForLevels(string $buildingKey, int $maxLevel = 20): array
    {
        $result = [];

        for ($lvl = 1; $lvl <= $maxLevel; $lvl++) {
            $result[$lvl] = $this->getEffects($buildingKey, $lvl);
        }

        return $result;
    }

    /**
     * Berechnet die Standardwerte aus der Config.
     */
    private function calculateBaseEffects(string $buildingKey, int $level): array
    {
        $effects = [];

        $config = config("game.building_progression.effect_configs.{$buildingKey}");
        $attributes = config("game.building_progression.effect_attributes.{$buildingKey}");

        if ($config && !empty($attributes)) {
            foreach ($attributes as $attr) {
                $effects[$attr] = $this->calculateEffect($config, $level);
            }
        }

        return $effects;
    }

    public function getBaseEffects(string $buildingKey, int $level): array
    {
        return $this->calculateBaseEffects($buildingKey, $level);
    }

    /**
     * Holt die zusätzlichen Effekte aus der DB (nur wenn vorhanden).
     */
    private function getExtraEffects(string $buildingKey, int $level): ?array
    {
        $levels = BuildingLevel::where('building_key', $buildingKey)
            ->where('level', '<=', $level)
            ->orderBy('level')
            ->get();

        $effects = [];
        $unlocks = [];

        Log::info("Lade DB-Effekte für {$buildingKey} bis Level {$level}: Gefundene Level: " . $levels->count());

        foreach ($levels as $entry) {
            foreach ($entry->effects as $key => $value) {
                if ($key === 'unlock') {
                    $unlocks = array_merge($unlocks, (array)$value);
                } else {
                    // Nur überschreiben, wenn Level höher ist als bisher
                    $effects[$key] = $value;
                }
            }
        }

        if (!empty($unlocks)) {
            $effects['unlock'] = array_unique($unlocks);
        }

        return !empty($effects) ? $effects : null;
    }

    private function calculateEffect(array $config, int $level): float
    {
        $base = $config['base_value'] ?? 0;
        $increment = $config['increment'] ?? 0;

        return match ($config['type']) {
            'multiplicative' => round($base * pow(1 + $increment, $level), 2),
            'exponential'    => floor($base * pow($increment, $level)),
            'additive'       => round($base + ($increment * $level), 2),
            default          => $base,
        };
    }
}
