<?php

namespace App\Models;

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
        return $this->belongsTo(BuildingDetails::class);
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

