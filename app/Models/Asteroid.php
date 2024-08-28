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
        'resource_pool',
        'resources',
        'x',
        'y',
        'pixel_size',
    ];
    
    protected $casts = [
        'resources' => 'array',
    ];

/*     public function resources()
    {
        return $this->belongsToMany(Resource::class, 'asteroid_resources', 'asteroid_id', 'resource_id');
    } */

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'resources' => $this->jsonSerialize()['resources'],
        ];
    }
}
