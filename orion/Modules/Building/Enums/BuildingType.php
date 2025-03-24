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
            self::SHIPYARD => ['production_speed' => ['modifier' => -1, 'replace' => true]],
            self::HANGAR => ['crew_limit' => ['modifier' => 0]],
            self::WAREHOUSE => ['storage' => ['modifier' => 0, 'multiply' => true]],
            self::LABORATORY => ['research_points' => ['modifier' => 0]],
            self::SCANNER => ['scan_range' => ['modifier' => 0]],
            self::SHIELD => ['base_defense' => ['modifier' => -1, 'replace' => true]],
        };
    }
}
