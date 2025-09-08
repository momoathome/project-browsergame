<?php

namespace Orion\Modules\Station\Models;

use Illuminate\Database\Eloquent\Model;

class StationRegion extends Model
{
        protected $fillable = [
        'x',
        'y',
        'used',
        'assigned_to_user_id'
    ];
}
