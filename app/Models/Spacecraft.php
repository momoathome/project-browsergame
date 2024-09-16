<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spacecraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'details_id',
        'combat',
        'cargo',
        'speed',
        'count',
        'build_time',
        'unitLimit',
        'unlocked',
    ];

    public function details()
    {
        return $this->belongsTo(SpacecraftDetails::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'spacecraft_resource_costs')
                    ->withPivot('amount')
                    ->withTimestamps();
    }
}
