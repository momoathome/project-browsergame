<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Combat\Dto\CombatResult;
use Orion\Modules\Combat\Dto\CombatRequest;
use Orion\Modules\Combat\Dto\CombatPlanRequest;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Asteroid\Services\AsteroidExplorer;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Spacecraft\Services\SpacecraftLockService;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Influence\Services\InfluenceService;
use Orion\Modules\Rebel\Services\RebelService;

readonly class CombatOrchestrationService
{
    public function __construct(
        private readonly CombatService $combatService,
        private readonly ActionQueueService $queueService,
        private readonly UserService $userService,
        private readonly SpacecraftService $spacecraftService,
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly CombatPlunderService $combatPlunderService,
        private readonly StationService $stationService,
        private readonly InfluenceService $influenceService,
        private readonly RebelService $rebelService,
        private readonly SpacecraftLockService $spacecraftLockService,
    ) {
    }

    /**
     * Plant und sendet einen Kampf in die Warteschlange
     */
    public function planAndQueueCombat($attacker, int $defenderId, array $attackerSpacecrafts, Station|Rebel $defenderStation, bool $isRebel): void
    {
        if ($isRebel) {
            $defender = $defenderStation; // Rebel als Verteidiger
        } else {
            $defender = $this->userService->find($defenderId);
        }
    
        // Erstelle einen Kampfplan mit allen notwendigen Informationen
        $combatPlanRequest = CombatPlanRequest::fromRequest(
            $attacker,
            $defender,
            $attackerSpacecrafts,
        );
    
        // Formatiere und bereite den Kampf vor
        $combatRequest = $this->combatService->prepareCombatPlan($combatPlanRequest);
    
        $result = $this->asteroidExplorer->resolveSpacecraftsAndIds($attacker, collect($attackerSpacecrafts));

        $spacecraftsWithDetails = $result['spacecraftsWithDetails'];
        $filteredSpacecraftsById = $result['spacecraftsById'];

        $duration = $this->asteroidExplorer->calculateTravelDuration(
            $spacecraftsWithDetails,
            $attacker,
            $defenderStation,
            QueueActionType::ACTION_TYPE_COMBAT
        );
    
        // Füge den Kampf zur Warteschlange hinzu
        $queueEntry = $this->queueService->addToQueue(
            $attacker->id,
            QueueActionType::ACTION_TYPE_COMBAT,
            $defenderId,
            $duration,
            $combatRequest->toArray()
        );
    
        // Sperre die Raumschiffe für andere Aktionen
        $this->spacecraftLockService->lockForQueue($queueEntry->id, $filteredSpacecraftsById);
    }
    
    /**
     * Führt einen geplanten Kampf durch
     */
    public function completeCombat(int $attackerId, int $defenderId, array $details, int $actionQueueId): CombatResult
    {
        $isRebelCombat = $details['is_rebel_combat'] ?? false;

        $attacker = $this->userService->find($attackerId);

        if ($isRebelCombat) {
            $defender = Rebel::find($defenderId);
            $defenderSpacecrafts = $this->rebelService->getAvailableSpacecraftsByIdWithDetails($defenderId);
        } else {
            $defender = $this->userService->find($defenderId);
            $defenderSpacecrafts = $this->spacecraftService->getAvailableSpacecraftsByUserIdWithDetails($defenderId);
        }

        $attackerSpacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($attackerId);
        $defenderFormatted = $this->combatService->formatDefenderSpacecrafts($defenderSpacecrafts);
        $attackerFormatted = $details['attacker_formatted'] ?? [];

        $combatRequest = new CombatRequest(
            $attackerId,
            $defenderId,
            $attackerFormatted,
            $defenderFormatted,
            $details['attacker_name'],
            $details['defender_name'],
            $details['attacker_coordinates'] ?? [],
            $details['target_coordinates'] ?? [],
            $isRebelCombat
        );

        // Simuliere den Kampf und speichere das Ergebnis
        $result = $this->combatService->executeCombat($combatRequest, $attacker, $defender);

        // calculate new spacecrafts count
        $attackerSpacecraftsCount = $this->combatService->calculateNewSpacecraftsCount(
            $attackerSpacecrafts, 
            $result->getLossesCollection('attacker')
        );
        $defenderSpacecraftsCount = $this->combatService->calculateNewSpacecraftsCount(
            $defenderSpacecrafts,
            $result->getLossesCollection('defender')
        );

        try {
            DB::transaction(function () use ($attacker, $defender, $attackerSpacecraftsCount, $defenderSpacecraftsCount, $attackerSpacecrafts, $isRebelCombat, $actionQueueId) {
                // Update spacecraft counts in database
                $this->spacecraftService->updateSpacecraftsCount($attacker->id, $attackerSpacecraftsCount);
                if ($isRebelCombat) {
                    $this->rebelService->updateSpacecraftsCount($defender->id, $defenderSpacecraftsCount);
                    /* $this->rebelService->updateLastInteraction($defender->id); */
                } else {
                    $this->spacecraftService->updateSpacecraftsCount($defender->id, $defenderSpacecraftsCount); 
                }

                $this->spacecraftLockService->freeForQueue($actionQueueId);
            });
        } catch (\Exception $e) {
            Log::error('Error occurred while completing combat: ' . $e->getMessage());
        }

        // Plündere Ressourcen, wenn der Angreifer gewonnen hat
        $plunderedResources = [];
        if ($result->winner === 'attacker') {
            $plunderedResources = $this->combatPlunderService->plunderResources(
                $attacker,
                $defender,
                $attackerSpacecraftsCount
            )->toArray();
        }

        $this->combatService->saveCombatResult(
            $attacker->id,
            $defender->id,
            $result,
            $plunderedResources,
            $isRebelCombat ? 'rebel' : 'user'
        );

        $this->influenceService->handleCombatResult(
            attacker: $attacker,
            defender: $defender,
            result: $result,
            attackerLosses: $result->getLossesCollection('attacker'),
            defenderLosses: $result->getLossesCollection('defender')
        );

        return $result;
    }
}
