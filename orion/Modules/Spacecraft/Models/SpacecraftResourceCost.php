<?php

namespace Orion\Modules\Spacecraft\Models;

use Orion\Modules\Resource\Models\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpacecraftResourceCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'spacecraft_id',
        'resource_id',
        'amount'
    ];

    public function spacecraft()
    {
        return $this->belongsTo(Spacecraft::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id', 'id', 'resources');
    }
}
