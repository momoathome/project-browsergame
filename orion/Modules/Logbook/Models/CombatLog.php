<?php

namespace Orion\Modules\Logbook\Models;

use App\Models\User;
use Orion\Modules\Rebel\Models\Rebel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CombatLog extends Model
{
    protected $fillable = [
        'attacker_id',
        'defender_id',
        'defender_type',
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
    public function defender()
    {
        return $this->morphTo(null, 'defender_type', 'defender_id');
    }

    protected static function booted()
    {
        Relation::morphMap([
            'user' => User::class,
            'rebel' => Rebel::class,
        ]);
    }
}
