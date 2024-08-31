<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coordinate_x',
        'coordinate_y'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
