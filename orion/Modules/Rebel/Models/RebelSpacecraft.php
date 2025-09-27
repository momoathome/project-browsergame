<?php

namespace Orion\Modules\Rebel\Models;


use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Spacecraft\Models\SpacecraftDetails;
use Illuminate\Database\Eloquent\Model;

class RebelSpacecraft extends Model
{
    protected $fillable = [
        'rebel_id',
        'details_id',
        'attack',
        'defense',
        'cargo',
        'speed',
        'operation_speed',
        'count',
        'locked_count',
        'build_time',
        'crew_limit',
        'research_cost',
        'unlocked',
    ];

    public function details()
    {
        return $this->belongsTo(SpacecraftDetails::class);
    }

    public function rebel()
    {
        return $this->belongsTo(Rebel::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'spacecraft_resource_costs')
                    ->withPivot('amount')
                    ->withTimestamps();
    }
}
