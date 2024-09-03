<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Asteroid extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'rarity',
        'base',
        'multiplier',
        'value',
        'x',
        'y',
        'pixel_size',
    ];

    public function resources()
    {
        return $this->hasMany(AsteroidResource::class);
    }

    public function toSearchableArray(): array
    {
        $resources = $this->resources()->pluck('resource_type')->toArray();
        $resources = implode(', ', $resources);

        return [
            'name' => $this->name,
            'rarity' => $this->rarity,
            'resources' => $resources,
        ];
    }

}
