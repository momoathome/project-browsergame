<?php

namespace Orion\Modules\Building\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'effect',
    ];

    public function buildings()
    {
        return $this->hasMany(Building::class, 'details_id');
    }
}

