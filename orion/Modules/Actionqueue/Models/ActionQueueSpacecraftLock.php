<?php

namespace Orion\Modules\Actionqueue\Models;

use Illuminate\Database\Eloquent\Model;
use Orion\Modules\Spacecraft\Models\SpacecraftDetails;

class ActionQueueSpacecraftLock extends Model
{
    protected $table = 'action_queue_spacecraft_locks';

    protected $fillable = [
        'action_queue_id',
        'spacecraft_details_id',
        'amount',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function actionQueue()
    {
        return $this->belongsTo(ActionQueue::class, 'action_queue_id');
    }

    public function spacecraftDetails()
    {
        return $this->belongsTo(SpacecraftDetails::class, 'spacecraft_details_id');
    }
}
