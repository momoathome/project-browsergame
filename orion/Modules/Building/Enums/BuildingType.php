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
                'increment' => 0.10,
                'description' => 'Erhöht die Produktionsgeschwindigkeit von Schiffen um 10% pro Level'
            ],
            self::HANGAR => [
                'type' => BuildingEffectType::ADDITIVE,
                'base_value' => 10,
                'increment' => 10,
                'description' => 'Erhöht die maximale Crew-Kapazität um 10 pro Level'
            ],
            self::WAREHOUSE => [
                'type' => BuildingEffectType::MULTIPLICATIVE,
                'base_value' => 1_500,
                'increment' => 0.3,
                'description' => 'Erhöht die Lagerkapazität um 30% pro Level'
            ],
            self::LABORATORY => [
                'type' => BuildingEffectType::ADDITIVE,
                'base_value' => 0,
                'increment' => 2,
                'description' => 'Erhöht die Forschungspunkte um 2 pro Level'
            ],
            self::SCANNER => [
                'type' => BuildingEffectType::ADDITIVE,
                'base_value' => 4_000,
                'increment' => 4_000,
                'description' => 'Erhöht die Scan-Reichweite um 4000 pro Level'
            ],
            self::SHIELD => [
                'type' => BuildingEffectType::MULTIPLICATIVE,
                'base_value' => 1.0,
                'increment' => 0.1,
                'description' => 'Erhöht die Basis-Verteidigung um 10% pro Level'
            ],
        };
    }
}
