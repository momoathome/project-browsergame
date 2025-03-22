<?php

namespace Orion\Modules\Building\Models;

use App\Models\User;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Building\Models\BuildingDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'details_id', // Referenz auf BuildingDetails
        'effect_value',
        'level',
        'build_time',
    ];

    public function details()
    {
        return $this->belongsTo(BuildingDetails::class, 'details_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'building_resource_costs')
                    ->withPivot('amount')
                    ->withTimestamps();
    }
}

