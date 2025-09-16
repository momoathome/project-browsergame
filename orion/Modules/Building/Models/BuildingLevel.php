<?php

namespace Orion\Modules\Building\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingLevel extends Model
{
    protected $fillable = 
    [
        'building_key', 
        'level', 
        'effects'
    ];

    protected $casts = [
        'effects' => 'array',
    ];
}
