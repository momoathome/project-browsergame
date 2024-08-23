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
        'buildTime',
        'cost',
    ];

    public function details()
    {
        return $this->belongsTo(BuildingDetails::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

