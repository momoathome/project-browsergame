<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'cost',
        'stock'
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
