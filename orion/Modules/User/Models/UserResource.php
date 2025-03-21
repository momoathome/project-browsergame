<?php

namespace Orion\Modules\User\Models;

use App\Models\User;
use Orion\Modules\Resource\Models\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_id',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
