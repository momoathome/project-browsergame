<?php

namespace Orion\Modules\Rebel\Models;

use Orion\Modules\Resource\Models\Resource;
use Illuminate\Database\Eloquent\Model;

class RebelResource extends Model
{
    protected $fillable = [
        'rebel_id',
        'resource_id',
        'amount',
    ];

    public function rebel()
    {
        return $this->belongsTo(Rebel::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
