<?php

namespace Orion\Modules\Building\Enums;

enum BuildingType: string
{
    case CORE = 'Core';
    case SHIPYARD = 'Shipyard';
    case HANGAR = 'Hangar';
    case WAREHOUSE = 'Warehouse';
    case LABORATORY = 'Laboratory';
    case SCANNER = 'Scanner';
    case SHIELD = 'Shield';

    public function getEffectAttributes(): array
    {
        return config('game.building_progression.effect_attributes')[$this->value] ?? [];
    }

    public function getEffectConfiguration(): array
    {
        return config('game.building_progression.effect_configs')[$this->value] ?? [];
    }
}
