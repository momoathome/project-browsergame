<?php

namespace Orion\Modules\Spacecraft\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpacecraftDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'image',
    ];

    public function buildings()
    {
        return $this->hasMany(Spacecraft::class, 'details_id');
    }
}
