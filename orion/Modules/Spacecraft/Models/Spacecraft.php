<?php

namespace Orion\Modules\Spacecraft\Models;


use App\Models\User;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Spacecraft\Models\SpacecraftDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'locked_count',
        'build_time',
        'crewLimit',
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
