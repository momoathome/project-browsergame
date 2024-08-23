<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsTo(Resource::class);
    }
}
