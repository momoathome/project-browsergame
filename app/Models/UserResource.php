<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_id',
        'count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resources()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
