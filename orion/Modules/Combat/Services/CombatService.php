<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Combat\Dto\Losses;
use Orion\Modules\Combat\Dto\Spacecraft;
use Orion\Modules\Combat\Dto\CombatResult;
use Orion\Modules\Combat\Dto\CombatRequest;
use Orion\Modules\Combat\Dto\CombatPlanRequest;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Combat\Repositories\CombatRepository;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

readonly class CombatService
{
    public function __construct(
        private readonly CombatRepository $combatRepository,
        private readonly SpacecraftService $spacecraftService,
        private readonly UserAttributeService $userAttributeService,
        private readonly StationService $stationService,
    ) {
    }

    /**
     * Simuliert einen Kampf zwischen zwei Flotten
     */
    public function simulateBattle(array $attacker, array $defender, $defenderUserId): CombatResult
    {
        $attackerShips = $this->convertToShipCollection($attacker);
        $defenderShips = $this->convertToShipCollection($defender);

        $totalCombatPower = $this->calculateTotalCombatPower($attackerShips, $defenderShips, $defenderUserId);
        $winner = $this->defineWinner($totalCombatPower['attacker'], $totalCombatPower['defender']);
        $losses = $this->calculateLosses($attackerShips, $defenderShips, $totalCombatPower['attacker'], $totalCombatPower['defender']);

        return new CombatResult($winner, $losses['attacker']->toArray(), $losses['defender']->toArray());
    }

    /**
     * Bereitet einen Kampfplan vor, mit allen benötigten Informationen
     */
     public function prepareCombatPlan(CombatPlanRequest $planRequest): CombatRequest
    {
        $defenderStation = $this->stationService->findStationByUserId($planRequest->defender->id);
        $attackerStation = $this->stationService->findStationByUserId($planRequest->attacker->id);
        
        $attackerFormatted = $this->formatAttackerSpacecrafts($planRequest->spacecrafts, $planRequest->attacker);
        $defenderFormatted = [];

        return new CombatRequest(
            $planRequest->attacker->id,
            $planRequest->defender->id,
            $attackerFormatted,
            $defenderFormatted,
            $planRequest->attacker->name,
            $planRequest->defender->name,
            [
                'x' => $attackerStation->x,
                'y' => $attackerStation->y,
            ],
            [
                'x' => $defenderStation->x,
                'y' => $defenderStation->y,
            ],
        );
    }

    /**
     * Bereitet das Format der Angreiferschiffe für den Kampf vor
     */
    public function formatAttackerSpacecrafts(array $spacecrafts, User $user): array
    {
        return collect($spacecrafts)
            ->map(function ($count, $name) use ($user) {
                $nameCollection = collect([$name => $count]);
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
        return $defender_spacecrafts
            ->filter(fn($spacecraft) => $spacecraft->available_count > 0)
            ->map(fn($spacecraft) => [
                'name' => $spacecraft->details->name,
                'combat' => $spacecraft->combat,
                'count' => $spacecraft->count,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Formatiert Raumschiffe für die Sperr-/Freigabe-Operationen
     */
    public function formatSpacecraftsForLocking($spacecrafts): Collection
    {
        $formatted = [];
        foreach ($spacecrafts as $spacecraft) {
            $formatted[$spacecraft['name']] = $spacecraft['count'];
        }

        return collect($formatted);
    }

    /**
     * Formatiert Spacecraft-Modelle für die Sperr-/Freigabe-Operationen
     */
    public function formatModelsForLocking(Collection $spacecrafts): Collection
    {
        return $spacecrafts->mapWithKeys(function ($spacecraft) {
            return [$spacecraft->details->name => $spacecraft->count];
        });
    }

    /**
     * Führt einen vollständigen Kampf aus
     */
    public function executeCombat(CombatRequest $combatRequest, User $attacker, User $defender): CombatResult
    {
        $result = $this->simulateBattle(
            $combatRequest->attackerSpacecrafts,
            $combatRequest->defenderSpacecrafts,
            $attacker->id
        );

        $result->attackerName = $combatRequest->attackerName;
        $result->defenderName = $combatRequest->defenderName;

        return $result;
    }

    public function saveCombatResult(int $attackerId, int $defenderId, CombatResult $result, array $plunderedResources = []): void
    {
        $this->combatRepository->saveCombatResult($attackerId, $defenderId, $result, $plunderedResources);
    }

    private function convertToShipCollection(array $ships): Collection
    {
        return collect($ships)->map(function ($ship) {
            return new Spacecraft($ship['name'], $ship['combat'], $ship['count']);
        });
    }

    private function calculateTotalCombatPower(Collection $attacker, Collection $defender, $userId): array
    {
        $calculateCombatPower = fn($ships) => $ships->sum(fn($ship) => $ship->combat * $ship->count);

        /* check if simulation or real fight */
        if ($userId) {
            $shield_base_defense = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::BASE_DEFENSE);
            $defense_multiplier = $shield_base_defense ? $shield_base_defense->attribute_value : 1;
        } else {
            $defense_multiplier = 1;
        }

        return [
            'attacker' => $calculateCombatPower($attacker),
            'defender' => $calculateCombatPower($defender) * $defense_multiplier,
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

    /**
     * Berechnet die neuen Raumschiffbestände nach einem Kampf
     */
    public function calculateNewSpacecraftsCount(Collection $spacecrafts, Collection $lossesCollection): Collection
    {
        return $spacecrafts
            ->map(function ($spacecraft) use ($lossesCollection) {
                $spacecraftName = $spacecraft->details->name;
                $loss = $lossesCollection->get($spacecraftName);
                $lostCount = $loss ? $loss->losses : 0;

                $updatedSpacecraft = clone $spacecraft;
                $updatedSpacecraft->count = max(0, $spacecraft->count - $lostCount);

                return $updatedSpacecraft;
            });
    }
}
