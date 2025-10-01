<?php

namespace Orion\Modules\Asteroid\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AsteroidSpawnRequest extends Model
{
    protected $fillable = [
        'asteroid_id',
        'user_id',
        'x',
        'y',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->with('stations');
    }
}
