<?php

namespace Orion\Modules\Market\Models;

use Orion\Modules\Resource\Models\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'category',
        'stock'
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
