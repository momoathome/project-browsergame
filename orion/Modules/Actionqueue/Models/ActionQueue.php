<?php

namespace Orion\Modules\Actionqueue\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;

class ActionQueue extends Model
{
    protected $table = 'action_queue';
    protected $fillable = ['user_id', 'action_type', 'target_id', 'start_time', 'end_time', 'status', 'details'];
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'details' => 'json',
        'action_type' => QueueActionType::class,
        'status' => QueueStatusType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingTimeAttribute()
    {
        return now()->diffForHumans($this->end_time, ['parts' => 2]);
    }
}
