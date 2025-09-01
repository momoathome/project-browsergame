<?php

namespace Orion\Modules\Building\Enums;

enum BuildingType: string
{
    case SHIPYARD = 'Shipyard';
    case HANGAR = 'Hangar';
    case WAREHOUSE = 'Warehouse';
    case LABORATORY = 'Laboratory';
    case SCANNER = 'Scanner';
    case SHIELD = 'Shield';
    
    public function getEffectAttributes(): array
    {
        return match($this) {
            self::SHIPYARD => ['production_speed'],
            self::HANGAR => ['crew_limit'],
            self::WAREHOUSE => ['storage'],
            self::LABORATORY => ['research_points'],
            self::SCANNER => ['scan_range'],
            self::SHIELD => ['base_defense'],
        };
    }
    
    public function getEffectConfiguration(): array
    {
        return match($this) {
            self::SHIPYARD => [
                'type' => BuildingEffectType::MULTIPLICATIVE,
                'base_value' => 1,
                'increment' => 0.05,
            ],
            self::HANGAR => [
                'type' => BuildingEffectType::EXPONENTIAL,
                'base_value' => 10,
                'increment' => 1.325,
            ],
            self::WAREHOUSE => [
                'type' => BuildingEffectType::EXPONENTIAL,
                'base_value' => 1_500,
                'increment' => 1.275,
            ],
            self::LABORATORY => [
                'type' => BuildingEffectType::ADDITIVE,
                'base_value' => 0,
                'increment' => 3,
            ],
            self::SCANNER => [
                'type' => BuildingEffectType::ADDITIVE,
                'base_value' => 5_000,
                'increment' => 2_500,
            ],
            self::SHIELD => [
                'type' => BuildingEffectType::MULTIPLICATIVE,
                'base_value' => 1.0,
                'increment' => 0.05,
            ],
        };
    }
}
