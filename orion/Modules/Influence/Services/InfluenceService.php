<?php

namespace Orion\Modules\Influence\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
    public function handleCombatResult(User $attacker, User $defender, $result, array $attackerLosses, array $defenderLosses, array $plunderedResources = []): void
    {
        $attackerLossCombatPower = $this->calculateLossesCombatPower($attackerLosses);
        $defenderLossCombatPower = $this->calculateLossesCombatPower($defenderLosses);

        $attackerTotalCombatPower = $result->attackerTotalCombatPower;
        $defenderTotalCombatPower = $result->defenderTotalCombatPower;

        $attackerDelta = 0;
        $defenderDelta = 0;

        if ($result->winner === 'attacker') {
            $totalCombatPower = max($attackerTotalCombatPower + $defenderTotalCombatPower, 1);
            $winBonus = ($totalCombatPower / 1000) + ($defenderLossCombatPower * 0.1);

            $attackerDelta += $defenderLossCombatPower / 50;
            $attackerDelta -= $attackerLossCombatPower / 75;
            $attackerDelta += $winBonus;

            $defenderDelta -= $defenderLossCombatPower / 100;
        } else {
            $totalCombatPower = max($attackerTotalCombatPower + $defenderTotalCombatPower, 1);
            $defenseBonus = ($totalCombatPower / 900) + ($attackerLossCombatPower * 0.1);

            $defenderDelta += $defenseBonus;
            $attackerDelta -= $attackerLossCombatPower / 75;
        }

        Log::info("Combat Influence Change: Attacker {$attacker->id} delta: {$attackerDelta}, Defender {$defender->id} delta: {$defenderDelta}");
        Log::info("Combat Losses: Attacker CP lost: {$attackerLossCombatPower}, Defender CP lost: {$defenderLossCombatPower}");

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
        $delta = $totalCost / 100; // Beispiel: 1 Punkt pro 500 Ressourcen

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
        $scalingFactor = 3; // je kleiner, desto stärker der Bonus bei hohen Kosten

        $delta = $researchCost * $baseFactor * (1 + ($researchCost / $scalingFactor));

        $this->applyInfluenceChange($userId, $delta, 'research_unlock', [
            'research_cost' => $researchCost
        ]);
    }

    /**
     * Einfluss durch Mining. Hier kannst du entscheiden ob pro Session oder pro 100 Ressourcen.
     */
    public function handleMining(int $userId, int $resourcesMined): void
    {
        $delta = $resourcesMined / 1000; // z. B. 1 Punkte pro 1000 Ressourcen

        $this->applyInfluenceChange($userId, $delta, 'mining', [
            'resources_mined' => $resourcesMined
        ]);
    }

    private function calculateLossesCombatPower(array $losses): float 
    {
        $total = 0;
        foreach ($losses as $loss) {
            // Annahme: $loss ist ein Objekt mit 'combat' und 'losses' Property
            $total += ($loss->combat ?? 0) * ($loss->losses ?? 0);
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
