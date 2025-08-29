<?php

namespace Orion\Modules\Logbook\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AsteroidMiningLog extends Model
{
    protected $fillable = [
        'user_id',
        'asteroid_id',
        'asteroid_info',
        'resources_extracted',
        'spacecrafts_used',
    ];

    protected $casts = [
        'resources_extracted' => 'array',
        'spacecrafts_used' => 'array',
        'asteroid_info' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
