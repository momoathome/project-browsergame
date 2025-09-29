<?php

namespace Orion\Modules\Influence\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserAttributeService;

class InfluenceService
{
    public function __construct(
        private readonly UserAttributeService $userAttributeService
    ) {
    }
    /**
     * Berechne und speichere Einfluss nach einem Kampf.
     */
    public function handleCombatResult(User|Rebel $attacker, User|Rebel $defender, $result, Collection $attackerLosses, Collection $defenderLosses): void
    {
        $attackerLossCombatPower = $this->calculateLossesCombatPower($attackerLosses);
        $defenderLossCombatPower = $this->calculateLossesCombatPower($defenderLosses);

        $attackerTotalCombatPower = $result->attackerTotalCombatPower;
        $defenderTotalCombatPower = $result->defenderTotalCombatPower;

        $attackerDelta = 0;
        $defenderDelta = 0;

        if ($result->winner === 'attacker') {
            $totalCombatPower = max($attackerTotalCombatPower + $defenderTotalCombatPower, 1);
            if (abs($attackerTotalCombatPower - $defenderTotalCombatPower) < 0.2 * $totalCombatPower) {
                $winBonus = min(($totalCombatPower / 200) + ($defenderLossCombatPower * 0.1), 100);
            } else {
                $winBonus = $totalCombatPower / 400;
            }

            $attackerDelta += $defenderLossCombatPower / 40;
            $attackerDelta -= $attackerLossCombatPower / 60;
            $attackerDelta += $winBonus;

            $defenderDelta -= $defenderLossCombatPower / 75;
        } else {
            $totalCombatPower = max($attackerTotalCombatPower + $defenderTotalCombatPower, 1);
        
            // Basis-Bonus
            $defenseBonus = ($totalCombatPower / 80);
        
            // Progressiver Bonus: Je mehr der Angreifer verliert, desto mehr bekommt der Verteidiger
            $attackerLossRatio = $attackerTotalCombatPower > 0
                ? $attackerLossCombatPower / $attackerTotalCombatPower
                : 0;
        
            // z.B. 0.2 Basis + progressiv bis zu 0.5 bei Totalverlust
            $defenseBonus += $attackerLossCombatPower * (0.2 + 0.3 * $attackerLossRatio);
        
            // Knapper Sieg-Bonus
            if (abs($attackerTotalCombatPower - $defenderTotalCombatPower) < 0.2 * $totalCombatPower) {
                $defenseBonus += $totalCombatPower / 100;
            }
        
            $defenderDelta += $defenseBonus;
            $defenderDelta -= $defenderLossCombatPower / 100;
        
            $attackerDelta -= $attackerLossCombatPower / 50;
        }

        Log::info("Combat Influence Change: Attacker delta: {$attackerDelta}, Defender delta: {$defenderDelta}");
        Log::info("Combat Losses: Attacker CP lost: {$attackerLossCombatPower}, Defender CP lost: {$defenderLossCombatPower}");
        Log::info("Combat Total CP: Attacker CP: {$attackerTotalCombatPower}, Defender CP: {$defenderTotalCombatPower}, total CP: {$totalCombatPower}");

        $this->applyInfluenceChange($attacker->id, $attackerDelta, 'combat', [
            'opponent' => $defender->id,
            'winner' => $result->winner,
            'defender_loss_cp' => $defenderLossCombatPower,
            'attacker_loss_cp' => $attackerLossCombatPower
        ]);

        $this->applyInfluenceChange($defender->id, $defenderDelta, 'combat', [
            'opponent' => $attacker->id,
            'winner' => $result->winner,
            'defender_loss_cp' => $defenderLossCombatPower,
            'attacker_loss_cp' => $attackerLossCombatPower
        ]);
    }

    /**
     * Einfluss nach einem abgeschlossenen Gebäude-Upgrade.
     * Wir nehmen hier die Kosten des Upgrades (die du bereits in startBuildingUpgrade hast).
     */
    public function handleBuildingUpgradeCompleted(int $userId, array $upgradeCosts): void
    {
        $totalCost = collect($upgradeCosts)->sum('amount');
        $delta = $totalCost / 20; // Beispiel: 1 Punkt pro 20 Ressourcen

        $this->applyInfluenceChange($userId, $delta, 'building_upgrade', [
            'costs' => $upgradeCosts
        ]);
    }

    /**
     * Einfluss durch Forschung (z. B. Freischalten von Raumschiffen).
     */
    public function handleResearchUnlock(int $userId, int $researchCost): void
    {
        $baseFactor = 10; // Basis-Influence pro Research Point
        $scalingFactor = 4; // je kleiner, desto stärker der Bonus bei hohen Kosten

        $delta = $researchCost * $baseFactor * (1 + ($researchCost / $scalingFactor));

        $this->applyInfluenceChange($userId, $delta, 'research_unlock', [
            'research_cost' => $researchCost
        ]);
    }

    /**
     * Einfluss durch Mining. Hier kannst du entscheiden ob pro Session oder pro 100 Ressourcen.
     * Not Used yet.
     */
    public function handleMining(int $userId, int $resourcesMined): void
    {
        $delta = $resourcesMined / 1000; // z. B. 1 Punkte pro 1000 Ressourcen

        $this->applyInfluenceChange($userId, $delta, 'mining', [
            'resources_mined' => $resourcesMined
        ]);
    }

    private function calculateLossesCombatPower(iterable $losses): float 
    {
        $total = 0;
        foreach ($losses as $loss) {
            // Annahme: $loss ist ein Objekt mit 'attack', 'defense', 'losses' Property
            $attack = is_array($loss) ? ($loss['attack'] ?? 0) : ($loss->attack ?? 0);
            $defense = is_array($loss) ? ($loss['defense'] ?? 0) : ($loss->defense ?? 0);
            $count = is_array($loss) ? ($loss['losses'] ?? 0) : ($loss->losses ?? 0);
            $combatPower = $attack + $defense;
            $total += $combatPower * $count;
        }

        return $total;
    }

    /**
     * Wendet eine Einfluss-Änderung an und speichert sie in der DB.
     */
    private function applyInfluenceChange(int $userId, float $delta, string $reason, array $meta = []): void
    {
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($userId, $delta, $reason, $meta) {
            // add delta with service
            $this->userAttributeService->updateUserAttribute($userId, UserAttributeType::INFLUENCE, floor($delta), false, false);

            // Optional: Log für Historie speichern
/*             DB::table('influence_logs')->insert([
                'user_id' => $userId,
                'delta' => $delta,
                'reason' => $reason,
                'meta' => json_encode($meta),
                'created_at' => now(),
            ]); */
        });
    }
}
