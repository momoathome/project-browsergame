<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schema_id', // Referenz auf BuildingSchema
        'effect_value',
        'level',
        'buildTime',
        'cost',
    ];

    public function schema()
    {
        return $this->belongsTo(BuildingSchema::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

