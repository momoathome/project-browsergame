<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionQueue extends Model
{
    protected $table = 'action_queue';
    protected $fillable = ['user_id', 'action_type', 'target_id', 'start_time', 'end_time', 'status', 'details'];
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'details' => 'json',
    ];

    // Definiere erlaubte Werte als Konstanten
    public const ACTION_TYPE_MINING = 'mining';
    public const ACTION_TYPE_BUILDING = 'building';
    public const ACTION_TYPE_PRODUCE = 'produce';
    public const ACTION_TYPE_TRADE = 'trade';
    public const ACTION_TYPE_COMBAT = 'combat';
    public const ACTION_TYPE_RESEARCH = 'research';
    
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    // Liste aller erlaubten Aktionstypen
    public static $allowedActionTypes = [
        self::ACTION_TYPE_MINING,
        self::ACTION_TYPE_BUILDING,
        self::ACTION_TYPE_PRODUCE,
        self::ACTION_TYPE_TRADE,
        self::ACTION_TYPE_COMBAT,
        self::ACTION_TYPE_RESEARCH
    ];

    // Liste aller erlaubten Status
    public static $allowedStatuses = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
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
