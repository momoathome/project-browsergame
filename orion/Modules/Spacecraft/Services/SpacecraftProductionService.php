<?php

namespace Orion\Modules\Spacecraft\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;
use Orion\Modules\Resource\Exceptions\InsufficientResourceException;
use Orion\Modules\Spacecraft\Exceptions\InsufficientCrewCapacityException;


class SpacecraftProductionService
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly SpacecraftRepository $spacecraftRepository,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService,
        private readonly ResourceService $resourceService
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
        try {
            // Kosten berechnen
            $totalCosts = $this->getSpacecraftsProductionCosts($spacecraft, $quantity);

            // Prüfen, ob genug Crew-Kapazität vorhanden ist
            $this->validateCrewCapacity($userId, $quantity);

            // Prüfen, ob genug Ressourcen vorhanden sind
            $this->userResourceService->validateUserHasEnoughResources($userId, $totalCosts);

            // Ressourcen abziehen und Produktion zur Queue hinzufügen
            DB::transaction(function () use ($userId, $quantity, $totalCosts, $spacecraft) {
                // Ressourcen abziehen
                $this->userResourceService->decrementUserResources($userId, $totalCosts);

                // Produktion zur Queue hinzufügen
                $this->addSpacecraftUpgradeToQueue($userId, $spacecraft, $quantity);
            });

            return [
                'success' => true,
                'message' => "Produktion von {$quantity} {$spacecraft->details->name} wurde gestartet"
            ];
        } catch (InsufficientCrewCapacityException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (InsufficientResourceException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            \Log::error("Fehler bei der Raumschiffproduktion:", [
                'user_id' => $userId,
                'spacecraft_id' => $spacecraft->id,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Fehler bei der Produktion: ' . $e->getMessage()
            ];
        }
    }

    private function getSpacecraftsProductionCosts(Spacecraft $spacecraft, int $quantity): Collection
    {
        return $spacecraft->resources->map(function ($resource) use ($quantity) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'amount' => $resource->pivot->amount * $quantity
            ];
        })->keyBy('id');
    }

    /**
     * Prüft, ob der Benutzer genügend Crew-Kapazität hat
     * 
     * @param int $userId ID des Benutzers
     * @param int $requiredCapacity Benötigte Kapazität
     * @throws InsufficientCrewCapacityException wenn nicht genügend Crew-Kapazität vorhanden ist
     */
    private function validateCrewCapacity(int $userId, int $requiredCapacity): void
    {
        $crewLimit = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::CREW_LIMIT);

        if (!$crewLimit || $crewLimit->attribute_value < $requiredCapacity) {
            throw new InsufficientCrewCapacityException();
        }
    }

    private function addSpacecraftUpgradeToQueue(int $userId, Spacecraft $spacecraft, int $quantity): void
    {
        $this->queueService->addToQueue(
            $userId,
            QueueActionType::ACTION_TYPE_PRODUCE,
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
        $researchPointsAttribute = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::RESEARCH_POINTS);

        if (!$researchPointsAttribute || $researchPointsAttribute->attribute_value < $spacecraft->research_cost) {
            return [
                'success' => false,
                'message' => 'Not enough research points'
            ];
        }

        try {
            DB::transaction(function () use ($userId, $spacecraft) {
                $this->userAttributeService->subtractAttributeAmount($userId, UserAttributeType::RESEARCH_POINTS, $spacecraft->research_cost);
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

    public function adminUnlockSpacecraft(int $userId, int $spacecraftId): array
    {
        $spacecraft = $this->spacecraftRepository->findSpacecraftById($spacecraftId, $userId);

        if (!$spacecraft) {
            return [
                'success' => false,
                'message' => 'Spacecraft not found'
            ];
        }

        DB::transaction(function () use ($userId, $spacecraft) {
            $spacecraft->unlocked = true;
            $spacecraft->save();
        });

        return [
            'success' => true,
            'message' => 'Spacecraft unlocked successfully'
        ];
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
        $spacecraft = $this->spacecraftRepository->findSpacecraftById($spacecraftId, $userId);

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
