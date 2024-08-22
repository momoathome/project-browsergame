<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingSchema extends Model
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
        return $this->hasMany(Building::class, 'schema_id');
    }
}

