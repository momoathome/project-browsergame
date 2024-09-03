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
        'resources',
        'x',
        'y',
        'pixel_size',
    ];
    
    protected $casts = [
        'resources' => 'array',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'resources' => $this->jsonSerialize()['resources'],
            'x' => $this->x,
            'y' => $this->y,
            'rarity' => $this->rarity,
            'base' => $this->base,
            'multiplier' => $this->multiplier,
            'value' => $this->value,
        ];
    }
}
