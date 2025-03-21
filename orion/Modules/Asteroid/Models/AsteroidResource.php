<?php

namespace Orion\Modules\Asteroid\Models;

use Illuminate\Database\Eloquent\Model;
use Orion\Modules\Asteroid\Models\Asteroid;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AsteroidResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'asteroid_id',
        'resource_type',
        'amount',
    ];

    protected $indexes = ['id', 'asteroid_id', 'resource_type', 'amount'];

    public function asteroid()
    {
        return $this->belongsTo(Asteroid::class);
    }
}
