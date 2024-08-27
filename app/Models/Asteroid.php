<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asteroid extends Model
{
    use HasFactory;

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

/*     public function resources()
    {
        return $this->belongsToMany(Resource::class, 'asteroid_resources', 'asteroid_id', 'resource_id');
    } */
}
