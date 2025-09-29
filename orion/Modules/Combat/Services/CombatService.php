<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Combat\Dto\Losses;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Combat\Dto\Spacecraft;
use Orion\Modules\Combat\Dto\CombatResult;
use Orion\Modules\Combat\Dto\CombatRequest;
use Orion\Modules\Combat\Dto\CombatPlanRequest;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Station\Services\StationService;
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
    public function simulateBattle(array $attacker, array $defender, $defenderId, $isRebelCombat): CombatResult
    {
        $attackerShips = $this->convertToShipCollection($attacker);
        $defenderShips = $this->convertToShipCollection($defender);

        $totalCombatPower = $this->calculateTotalCombatPower($attackerShips, $defenderShips, $defenderId, $isRebelCombat);
        $winner = $this->defineWinner($totalCombatPower['attacker'], $totalCombatPower['defender']);
        $losses = $this->calculateLosses($attackerShips, $defenderShips, $totalCombatPower['attacker'], $totalCombatPower['defender']);

        return new CombatResult(
            $winner, 
            $losses['attacker']->toArray(), 
            $losses['defender']->toArray(),
            $totalCombatPower['attacker'],
            $totalCombatPower['defender']
        );
    }

    /**
     * Bereitet einen Kampfplan vor, mit allen benötigten Informationen
     */
     public function prepareCombatPlan(CombatPlanRequest $planRequest): CombatRequest
    {
        $defenderStation = $this->getStationForEntity($planRequest->defender);
        $attackerStation = $this->getStationForEntity($planRequest->attacker);
        
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
            $planRequest->isRebelCombat
        );
    }

    private function getStationForEntity(User|Rebel $entity)
    {
        if ($entity instanceof User) {
            return $this->stationService->findStationByUserId($entity->id);
        }
        if ($entity instanceof Rebel) {
            return $entity; // Rebel hat x,y direkt
        }
        throw new \InvalidArgumentException('Unknown entity type');
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
                    'attack' => $spacecraft ? $spacecraft->attack : 0,
                    'defense' => $spacecraft ? $spacecraft->defense : 0,
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
            ->filter(fn($spacecraft) =>
                (isset($spacecraft->available_count) ? $spacecraft->available_count : $spacecraft->count) > 0)
            ->map(fn($spacecraft) => [
                'name' => $spacecraft->details->name,
                'attack' => $spacecraft->attack,
                'defense' => $spacecraft->defense,
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
    public function executeCombat(CombatRequest $combatRequest, User|Rebel $attacker, User|Rebel $defender): CombatResult
    {
        $result = $this->simulateBattle(
            $combatRequest->attackerSpacecrafts,
            $combatRequest->defenderSpacecrafts,
            $defender->id,
            $combatRequest->isRebelCombat
        );

        $result->attackerName = $combatRequest->attackerName;
        $result->defenderName = $combatRequest->defenderName;

        return $result;
    }

    public function saveCombatResult(int $attackerId, int $defenderId, CombatResult $result, array $plunderedResources = [], string $defenderType = 'user'): void
    {
        $this->combatRepository->saveCombatResult($attackerId, $defenderId, $result, $plunderedResources, $defenderType);
    }

    private function convertToShipCollection(array $ships): Collection
    {
        return collect($ships)->map(function ($ship) {
                return new Spacecraft(
                    $ship['name'],
                    $ship['attack'],
                    $ship['defense'],
                    $ship['count']
                );
            });
    }

    private function calculateTotalCombatPower(Collection $attacker, Collection $defender, $defenderId, $isRebelCombat): array
    {
        $calculateAttackPower = fn($ships) => $ships->sum(fn($ship) => $ship->attack * $ship->count);
        $calculateDefensePower = fn($ships) => $ships->sum(fn($ship) => $ship->defense * $ship->count);

        /* check if simulation or real fight */
        if ($defenderId && !$isRebelCombat) {
            $base_defense = $this->userAttributeService->getSpecificUserAttribute($defenderId, UserAttributeType::BASE_DEFENSE);
            $defense_multiplier = $base_defense ? $base_defense->attribute_value : 1;
        } else {
            $defense_multiplier = 1;
        }

        return [
            'attacker' => $calculateAttackPower($attacker),
            'defender' => $calculateDefensePower($defender) * $defense_multiplier,
        ];
    }

    private function defineWinner(float $attackerTotalCombatPower, float $defenderTotalCombatPower): string
    {
        return $attackerTotalCombatPower > $defenderTotalCombatPower ? 'attacker' : 'defender';
    }

    private function getRandomArbitrary(float $min, float $max): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 3);
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
                return new Losses(
                    $ship->name,
                    $ship->count,
                    $isWinner ? $losses : $ship->count,
                    $ship->attack,
                    $ship->defense
                );
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
