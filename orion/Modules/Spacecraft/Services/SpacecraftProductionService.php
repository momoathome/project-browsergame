<?php

namespace Orion\Modules\Spacecraft\Services;

use Illuminate\Support\Facades\DB;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\User\Services\UserResourceService;

class SpacecraftProductionService
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly SpacecraftRepository $spacecraftRepository,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService
    ) {
    }

    /**
     * Versucht, ein Raumschiff zu produzieren
     * 
     * @param int $userId ID des Benutzers
     * @param Spacecraft $spacecraft Das zu produzierende Raumschiff
     * @param int $quantity Die gewünschte Anzahl
     * @return array ['success' => bool, 'message' => string]
     */
    public function startSpacecraftProduction(int $userId, Spacecraft $spacecraft, int $quantity): array
    {
        // Kosten berechnen
        $totalCosts = $this->getSpacecraftsUpgradeCosts($spacecraft, $quantity);
    
        // Prüfen, ob genug Crew-Kapazität vorhanden ist
        $crewLimit = $this->userAttributeService->getSpecificUserAttribute($userId, 'crew_limit');
    
        // Prüfen, ob genug Ressourcen vorhanden sind
        foreach ($totalCosts as $resourceId => $requiredResource) {
            $userResource = $this->userResourceService->getSpecificUserResource($userId, $resourceId);
            
            if (!$userResource || $userResource->amount < $requiredResource) {
                return [
                    'success' => false,
                    'message' => 'Nicht genügend Ressourcen vorhanden'
                ];
            }
        }
    
        try {
            // Ressourcen abziehen und Produktion zur Queue hinzufügen
            DB::transaction(function () use ($userId, $quantity, $totalCosts, $spacecraft) {
                // Ressourcen abziehen
                $this->decrementResourcesFromUser($userId, $totalCosts);
    
                // Produktion zur Queue hinzufügen
                $this->addSpacecraftUpgradeToQueue($userId, $spacecraft, $quantity);
            });
            
            return [
                'success' => true,
                'message' => "Produktion von {$quantity} {$spacecraft->details->name} wurde gestartet"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Fehler bei der Produktion: ' . $e->getMessage()
            ];
        }
    }

    private function getSpacecraftsUpgradeCosts(Spacecraft $spacecraft, int $quantity): array
    {
        return $spacecraft->resources->mapWithKeys(function ($resource) use ($quantity) {
            return [$resource->id => $resource->pivot->amount * $quantity];
        })->toArray();
    }

    private function decrementResourcesFromUser(int $userId, array $totalCosts): void
    {
        foreach ($totalCosts as $resourceId => $resource) {
            $this->userResourceService->subtractResourceAmount($userId, $resourceId, $resource);
        }
    }

    private function addSpacecraftUpgradeToQueue(int $userId, Spacecraft $spacecraft, int $quantity): void
    {
        $this->queueService->addToQueue(
            $userId,
            ActionQueue::ACTION_TYPE_PRODUCE,
            $spacecraft->id,
            $spacecraft->build_time * $quantity,
            [
                'spacecraft_name' => $spacecraft->details->name,
                'quantity' => $quantity,
            ]
        );
    }

    /**
     * Entsperrt ein Raumschiff für den Nutzer
     * 
     * @param int $userId ID des Benutzers
     * @param Spacecraft $spacecraft Das zu entsperrende Raumschiff
     * @return array ['success' => bool, 'message' => string]
     */
    public function unlockSpacecraft(int $userId, Spacecraft $spacecraft): array
    {
        $researchPointsAttribute = $this->userAttributeService->getSpecificUserAttribute($userId, 'research_points');

        if (!$researchPointsAttribute || $researchPointsAttribute->attribute_value < $spacecraft->research_cost) {
            return [
                'success' => false,
                'message' => 'Not enough research points'
            ];
        }

        try {
            DB::transaction(function () use ($userId, $spacecraft) {
                $this->userAttributeService->subtractAttributeAmount($userId, 'research_points', $spacecraft->research_cost);
                $spacecraft->unlocked = true;
                $spacecraft->save();
            });

            return [
                'success' => true,
                'message' => 'Spacecraft unlocked successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error during unlocking: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Schließt die Produktion eines Raumschiffs ab
     * 
     * @param int $spacecraftId ID des Raumschiffs
     * @param int $userId ID des Benutzers
     * @param mixed $details Details zur Produktion
     * @return bool
     */
    public function completeProduction(int $spacecraftId, int $userId, $details): bool
    {
        $spacecraft = Spacecraft::where('id', $spacecraftId)
            ->where('user_id', $userId)
            ->first();

        if (!$spacecraft) {
            return false;
        }

        try {
            return DB::transaction(function () use ($spacecraft, $details) {
                if (is_string($details)) {
                    $details = json_decode($details, true);
                }
                $quantity = $details['quantity'];

                $spacecraft->count += $quantity;
                $spacecraft->save();

                return true;
            });
        } catch (\Exception $e) {
            return false;
        }
    }
}
