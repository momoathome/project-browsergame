<?php

namespace Orion\Modules\Combat\Repositories;

use Orion\Modules\Combat\Models\CombatLog;
use Orion\Modules\Combat\Dto\CombatResult;

readonly class CombatRepository
{
    /**
     * Speichert ein Kampfergebnis in der Datenbank
     */
    public function saveCombatResult(int $attackerId, int $defenderId, CombatResult $result, array $plunderedResources = []): void
    {
        CombatLog::create([
            'attacker_id' => $attackerId,
            'defender_id' => $defenderId,
            'winner' => $result->winner,
            'attacker_losses' => $result->attackerLosses,
            'defender_losses' => $result->defenderLosses,
            'plundered_resources' => $plunderedResources,
            'date' => now()
        ]);
    }
    
    /**
     * Holt die letzten Kampfergebnisse für einen Benutzer
     */
    public function getRecentCombatsForUser(int $userId, int $limit = 10)
    {
        return CombatLog::where('attacker_id', $userId)
            ->orWhere('defender_id', $userId)
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();
    }
}
