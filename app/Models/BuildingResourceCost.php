<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingResourceCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'resource_id',
        'amount'
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}

