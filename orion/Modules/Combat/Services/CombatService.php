<?php

namespace Orion\Modules\Combat\Services;

use Orion\Modules\Combat\Dto\Losses;
use Orion\Modules\Combat\Dto\Spacecraft;
use Orion\Modules\Combat\Dto\CombatResult;
use Illuminate\Database\Eloquent\Collection;
use Orion\Modules\Combat\Repositories\CombatRepository;

readonly class CombatService
{

    public function __construct(private CombatRepository $combatRepository)
    {
    }

    // Add service logic here
    public function simulateBattle(array $attacker, array $defender): CombatResult
    {
        $attackerShips = $this->convertToShipCollection($attacker);
        $defenderShips = $this->convertToShipCollection($defender);

        $totalCombatPower = $this->calculateTotalCombatPower($attackerShips, $defenderShips);
        $winner = $this->defineWinner($totalCombatPower['attacker'], $totalCombatPower['defender']);
        $losses = $this->calculateLosses($attackerShips, $defenderShips, $totalCombatPower['attacker'], $totalCombatPower['defender']);

        return new CombatResult($winner, $losses['attacker']->toArray(), $losses['defender']->toArray());
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
