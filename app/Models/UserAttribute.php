<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attribute_name',
        'attribute_value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
