<?php

namespace Orion\Modules\User\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
