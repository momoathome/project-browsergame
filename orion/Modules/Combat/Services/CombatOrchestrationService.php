<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Combat\Dto\CombatResult;
use Orion\Modules\Combat\Dto\CombatRequest;
use Orion\Modules\Combat\Dto\CombatPlanRequest;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Asteroid\Services\AsteroidExplorer;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

readonly class CombatOrchestrationService
{
    public function __construct(
        private readonly CombatService $combatService,
        private readonly ActionQueueService $queueService,
        private readonly UserService $userService,
        private readonly SpacecraftService $spacecraftService,
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly CombatPlunderService $combatPlunderService
    ) {
    }

    /**
     * Plant und sendet einen Kampf in die Warteschlange
     */
    public function planAndQueueCombat($attacker, int $defenderId, array $spacecrafts, Station $defenderStation): void
    {
        $defender = $this->userService->find($defenderId);
        $defenderSpacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($defenderId);
        
        // Erstelle einen Kampfplan mit allen notwendigen Informationen
        $combatPlanRequest = CombatPlanRequest::fromRequest(
            $attacker,
            $defender,
            $spacecrafts,
            $defenderSpacecrafts
        );
        
        // Formatiere und bereite den Kampf vor
        $combatRequest = $this->combatService->prepareCombatPlan($combatPlanRequest);
        
        // Formatiere die Raumschiffe des Angreifers für die Sperre
        $filteredSpacecrafts = $this->combatService->formatSpacecraftsForLocking($combatRequest->attackerSpacecrafts);
        $spacecraftsWithDetails = $this->asteroidExplorer->getSpacecraftsWithDetails($attacker, $filteredSpacecrafts);
        
        // Berechne die Reisedauer
        $duration = $this->asteroidExplorer->calculateTravelDuration(
            $spacecraftsWithDetails,
            $attacker,
            $defenderStation,
            QueueActionType::ACTION_TYPE_COMBAT
        );
        
        // Sperre die Raumschiffe für andere Aktionen
        $this->spacecraftService->lockSpacecrafts($attacker, $filteredSpacecrafts);
        
        // Füge den Kampf zur Warteschlange hinzu
        $this->queueService->addToQueue(
            $attacker->id,
            QueueActionType::ACTION_TYPE_COMBAT,
            $defenderId,
            $duration,
            $combatRequest->toArray()
        );
    }
    
    /**
     * Führt einen geplanten Kampf durch
     */
    public function completeCombat(int $attackerId, int $defenderId, array $details): CombatResult
    {
        $combatRequest = CombatRequest::fromArray([
            'attacker_id' => $attackerId,
            'defender_id' => $defenderId,
            'attacker_formatted' => $details['attacker_formatted'],
            'defender_formatted' => $details['defender_formatted'],
            'attacker_name' => $details['attacker_name'],
            'defender_name' => $details['defender_name']
        ]);

        $attacker = $this->userService->find($attackerId);
        $defender = $this->userService->find($defenderId);
        
        // Simuliere den Kampf und speichere das Ergebnis
        $result = $this->combatService->executeCombat($combatRequest, $attacker, $defender);

        $attackerSpacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($attackerId);
        $defenderSpacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($defenderId);

        // calculate new spacecrafts count
        $attackerSpacecraftsCount = $this->combatService->calculateNewSpacecraftsCount(
            $attackerSpacecrafts, 
            $result->getLossesCollection('attacker')
        );
        $defenderSpacecraftsCount = $this->combatService->calculateNewSpacecraftsCount(
            $defenderSpacecrafts,
            $result->getLossesCollection('defender')
        );

        // Update spacecrafts count in database
        $this->spacecraftService->updateSpacecraftsCount($attacker->id, $attackerSpacecraftsCount);
        $this->spacecraftService->updateSpacecraftsCount($defender->id, $defenderSpacecraftsCount); 

        // Free spacecrafts from locked_count
        $formattedSpacecrafts = $this->combatService->formatModelsForLocking($attackerSpacecrafts);
        $this->spacecraftService->freeSpacecrafts($attacker, $formattedSpacecrafts);
        
        // Plündere Ressourcen, wenn der Angreifer gewonnen hat
        if ($result->winner === 'attacker') {
            $this->combatPlunderService->plunderResources(
                $attacker,
                $defender,
                $attackerSpacecraftsCount
            );
        }
        
        return $result;
    }
}
