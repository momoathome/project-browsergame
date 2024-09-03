<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsteroidResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'asteroid_id',
        'resource_type',
        'amount',
    ];

    public function asteroid()
    {
        return $this->belongsTo(Asteroid::class);
    }
}
