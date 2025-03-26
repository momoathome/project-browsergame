<?php

namespace Orion\Modules\ActionQueueArchive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;

class ActionQueueArchive extends Model
{
    protected $table = 'action_queue_archives';
    
    protected $fillable = [
        'user_id', 
        'action_type', 
        'target_id', 
        'start_time', 
        'end_time', 
        'status', 
        'details'
    ];
    
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
    
    // Optional: Berechnete Attribute für Statistiken
    public function getDurationAttribute()
    {
        return $this->start_time->diffInSeconds($this->end_time);
    }

    // Methode für Statistiken (kann später implementiert werden)
    public static function getCompletedActionsByType($userId, $period = 'month')
    {
        // Implementierung für Statistik-Auswertungen
    }
}
