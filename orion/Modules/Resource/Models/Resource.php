<?php

namespace Orion\Modules\Resource\Models;

use Illuminate\Database\Eloquent\Model;
use Orion\Modules\Market\Models\Market;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image'
    ];

    public function market()
    {
        return $this->hasOne(Market::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'user_resources', 'user_id', 'resource_id')
            ->withPivot('amount');
    }
}
