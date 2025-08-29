<?php

namespace Orion\Modules\Logbook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CombatLog extends Model
{
    protected $fillable = [
        'attacker_id',
        'defender_id',
        'winner',
        'attacker_losses',
        'defender_losses',
        'plundered_resources',
        'date'
    ];

    protected $casts = [
        'attacker_losses' => 'array',
        'defender_losses' => 'array',
        'plundered_resources' => 'array',
        'date' => 'datetime'
    ];

    /**
     * Beziehung zum Angreifer
     */
    public function attacker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attacker_id');
    }

    /**
     * Beziehung zum Verteidiger
     */
    public function defender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defender_id');
    }
}
