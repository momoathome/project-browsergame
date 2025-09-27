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
        'resource_cap',
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

    public function resources()
    {
        return $this->hasMany(RebelResource::class, 'rebel_id');
    }

    public function spacecrafts()
    {
        return $this->hasMany(RebelSpacecraft::class, 'rebel_id');
    }
}

