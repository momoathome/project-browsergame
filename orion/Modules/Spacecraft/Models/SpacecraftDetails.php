<?php

namespace Orion\Modules\Spacecraft\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orion\Modules\Rebel\Models\RebelSpacecraft;

class SpacecraftDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'image',
    ];

    public function spacecrafts()
    {
        return $this->hasMany(Spacecraft::class, 'details_id');
    }

    public function rebelSpacecrafts()
    {
        return $this->hasMany(RebelSpacecraft::class, 'details_id');
    }
}
