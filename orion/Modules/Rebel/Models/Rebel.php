<?php

namespace Orion\Modules\Rebel\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class Rebel extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
        'faction',
        'x',
        'y',
        'difficulty_level',
        'last_interaction',
        'defeated_count',
        'fleet_cap',
        'fleet_growth_rate',
        'loot_multiplier',
        'adaptation_level',
        'behavior',
        'base_chance',
    ];

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'faction' => $this->faction,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}

