<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
