<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use Orion\Modules\Combat\Dto\Losses;
use Orion\Modules\Combat\Dto\Spacecraft;
use Orion\Modules\Combat\Dto\CombatRequest;
use Orion\Modules\Combat\Dto\CombatResult;
use Orion\Modules\Combat\Dto\CombatPlanRequest;
use Illuminate\Support\Collection;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Combat\Repositories\CombatRepository;

readonly class CombatService
{
    public function __construct(
        private readonly CombatRepository $combatRepository,
        private readonly SpacecraftService $spacecraftService
    ) {
    }

    /**
     * Simuliert einen Kampf zwischen zwei Flotten
     */
    public function simulateBattle(array $attacker, array $defender): CombatResult
    {
        $attackerShips = $this->convertToShipCollection($attacker);
        $defenderShips = $this->convertToShipCollection($defender);

        $totalCombatPower = $this->calculateTotalCombatPower($attackerShips, $defenderShips);
        $winner = $this->defineWinner($totalCombatPower['attacker'], $totalCombatPower['defender']);
        $losses = $this->calculateLosses($attackerShips, $defenderShips, $totalCombatPower['attacker'], $totalCombatPower['defender']);

        return new CombatResult($winner, $losses['attacker']->toArray(), $losses['defender']->toArray());
    }

    /**
     * Bereitet einen Kampfplan vor, mit allen benötigten Informationen
     */
    public function prepareCombatPlan(CombatPlanRequest $planRequest): CombatRequest
    {
        $attacker_formatted = $this->formatAttackerSpacecrafts($planRequest->spacecrafts, $planRequest->attacker);
        $defender_formatted = $this->formatDefenderSpacecrafts($planRequest->defenderSpacecrafts);

        return new CombatRequest(
            $planRequest->attacker->id,
            $planRequest->defenderId,
            $attacker_formatted,
            $defender_formatted,
            $planRequest->attacker->name,
            $planRequest->defenderName
        );
    }

    /**
     * Bereitet das Format der Angreiferschiffe für den Kampf vor
     */
    public function formatAttackerSpacecrafts(array $spacecrafts, User $user): array
    {
        return collect($spacecrafts)
            ->map(function ($count, $name) use ($user) {
                // Hier ist der Fehler - wir wandeln den String in eine Collection um
                $nameCollection = collect([$name]);
                $spacecraft = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($user->id, $nameCollection)->first();

                return [
                    'name' => $name,
                    'combat' => $spacecraft ? $spacecraft->combat : 0,
                    'count' => $count,
                ];
            })
            ->filter(function ($spacecraft) {
                return $spacecraft['count'] > 0;
            })
            ->values()
            ->toArray();
    }

    /**
     * Bereitet das Format der Verteidigerschiffe für den Kampf vor
     */
    public function formatDefenderSpacecrafts($defender_spacecrafts): array
    {
        return $defender_spacecrafts->map(function ($spacecraft) {
            return [
                'name' => $spacecraft->details->name,
                'combat' => $spacecraft->combat,
                'count' => $spacecraft->count,
            ];
        })->toArray();
    }

    /**
     * Formatiert Raumschiffe für die Sperr-/Freigabe-Operationen
     */
    public function formatSpacecraftsForLocking($spacecrafts)
    {
        $formatted = [];
        foreach ($spacecrafts as $spacecraft) {
            $formatted[$spacecraft['name']] = $spacecraft['count'];
        }

        return collect($formatted);
    }

    /**
     * Führt einen vollständigen Kampf aus
     */
    public function executeCombat(CombatRequest $combatRequest, User $attacker, User $defender): CombatResult
    {
        $result = $this->simulateBattle(
            $combatRequest->attackerSpacecrafts,
            $combatRequest->defenderSpacecrafts
        );

        $result->attackerName = $combatRequest->attackerName;
        $result->defenderName = $combatRequest->defenderName;

        // Speichere Kampfergebnis in die Datenbank
/*         $this->combatRepository->saveCombatResult(
            $combatRequest->attackerId,
            $combatRequest->defenderId,
            $result
        ); */

        return $result;
    }

    private function convertToShipCollection(array $ships): Collection
    {
        return collect($ships)->map(function ($ship) {
            return new Spacecraft($ship['name'], $ship['combat'], $ship['count']);
        });
    }

    private function calculateTotalCombatPower(Collection $attacker, Collection $defender): array
    {
        $calculateCombatPower = fn($ships) => $ships->sum(fn($ship) => $ship->combat * $ship->count);

        return [
            'attacker' => $calculateCombatPower($attacker),
            'defender' => $calculateCombatPower($defender),
        ];
    }

    private function defineWinner(float $attackerTotalCombatPower, float $defenderTotalCombatPower): string
    {
        return $attackerTotalCombatPower > $defenderTotalCombatPower ? 'attacker' : 'defender';
    }

    private function getRandomArbitrary(float $min, float $max): float
    {
        return round(($min + lcg_value() * (abs($max - $min))), 3);
    }

    private function calculateLuckModifier(float $winnerCombatValue, float $looserCombatValue): float
    {
        $ranges = [
            ['min' => 5, 'max' => 10, 'minModifier' => 0.7, 'maxModifier' => 1.2],
            ['min' => 10, 'max' => 20, 'minModifier' => 0.4, 'maxModifier' => 0.8],
            ['min' => 20, 'max' => 50, 'minModifier' => 0.2, 'maxModifier' => 0.5],
            ['min' => 50, 'max' => PHP_FLOAT_MAX, 'minModifier' => 0.05, 'maxModifier' => 0.15]
        ];

        $luckModifier = $this->getRandomArbitrary(0.8, 1.4);

        foreach ($ranges as $range) {
            if ($winnerCombatValue >= $looserCombatValue * $range['min'] && $winnerCombatValue < $looserCombatValue * $range['max']) {
                $luckModifier = $this->getRandomArbitrary($range['minModifier'], $range['maxModifier']);
                break;
            }
        }

        return $luckModifier;
    }

    private function calculateLossRatio(float $attackerTotalCombatPower, float $defenderTotalCombatPower, string $winner): float
    {
        [$winnerCombatValue, $looserCombatValue] = $winner === 'attacker'
            ? [$attackerTotalCombatPower, $defenderTotalCombatPower]
            : [$defenderTotalCombatPower, $attackerTotalCombatPower];

        // Wenn der Gewinner einen Kampfwert von 0 hat, gibt es ein Problem mit den Daten
        // Oder wenn beide 0 sind, dann ist es ein leerer Kampf
        if ($winnerCombatValue <= 0) {
            // Falls der Gewinner keinen Kampfwert hat, fällt nichts an
            return 0;
        }

        // Wenn der Verlierer einen Kampfwert von 0 hat, gibt es keine Verluste für den Gewinner
        if ($looserCombatValue <= 0) {
            return 0;
        }

        $luckModifier = $this->calculateLuckModifier($winnerCombatValue, $looserCombatValue);
        $looserWinnerRatio = round($looserCombatValue / $winnerCombatValue, 3);
        $lossRatio = round($looserWinnerRatio * $luckModifier, 3);

        return max(0, min(1, $lossRatio));
    }

    private function calculateLosses(Collection $attacker, Collection $defender, float $attackerTotalCombatPower, float $defenderTotalCombatPower): array
    {
        $winner = $this->defineWinner($attackerTotalCombatPower, $defenderTotalCombatPower);
        $lossRatio = $this->calculateLossRatio($attackerTotalCombatPower, $defenderTotalCombatPower, $winner);

        $calculateLosses = function (Collection $spacecrafts, bool $isWinner) use ($lossRatio) {
            return $spacecrafts->map(function (Spacecraft $ship) use ($lossRatio, $isWinner) {
                $losses = $ship->count === 0 ? 0 : round($ship->count * $lossRatio);
                return new Losses($ship->name, $ship->count, $isWinner ? $losses : $ship->count);
            });
        };

        return [
            'attacker' => $calculateLosses($attacker, $winner === 'attacker'),
            'defender' => $calculateLosses($defender, $winner === 'defender'),
        ];
    }
}
