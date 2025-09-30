<?php

namespace Orion\Modules\Spacecraft\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Influence\Services\InfluenceService;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Building\Services\BuildingEffectService;
use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;
use Orion\Modules\User\Exceptions\InsufficientResourceException;
use Orion\Modules\Spacecraft\Exceptions\InsufficientCrewCapacityException;


class SpacecraftProductionService
{
    public function __construct(
        private readonly ActionQueueService $queueService,
        private readonly SpacecraftRepository $spacecraftRepository,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService,
        private readonly ResourceService $resourceService,
        private readonly UserService $userService,
        private readonly InfluenceService $influenceService
    ) {
    }

    public function startSpacecraftProduction(User $user, Spacecraft $spacecraft, int $quantity): array
    {
        $queuedQuantity = $this->queueService->getQueuedUpgrades($user->id, $spacecraft->id, QueueActionType::ACTION_TYPE_PRODUCE)
            ->sum(function ($entry) {
                $details = is_string($entry->details) ? json_decode($entry->details, true) : $entry->details;
                return $details['quantity'] ?? 0;
            });

        $targetQuantity = $spacecraft->count + $queuedQuantity + $quantity;

        try {
            DB::transaction(function () use ($user, $spacecraft, $quantity, $targetQuantity) {
                $totalCosts = $this->getSpacecraftsProductionCosts($spacecraft, $quantity);

                $this->validateCrewCapacity($user->id, $spacecraft->id, $quantity);

                $this->userResourceService->validateUserHasEnoughResources($user->id, $totalCosts);

                $this->userResourceService->decrementUserResources($user, $totalCosts);

                $this->addSpacecraftUpgradeToQueue($user->id, $spacecraft, $quantity, $targetQuantity);
            });

            broadcast(new UpdateUserResources($user));
            return [
                'success' => true,
                'message' => "Production of {$spacecraft->details->name} x{$quantity} successfully started"
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
            \Log::error("Error occurred while starting spacecraft production:", [
                'user_id' => $user->id,
                'spacecraft_id' => $spacecraft->id,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error occurred while starting spacecraft production: ' . $e->getMessage()
            ];
        }
    }

    public function cancelSpacecraftProduction(User $user, Spacecraft $spacecraft): array
    {
        $queueEntry = $this->queueService->getInProgressQueuesFromUserByType($user->id, QueueActionType::ACTION_TYPE_PRODUCE)
            ->where('target_id', $spacecraft->id)
            ->first();

        if (!$queueEntry) {
            return [
                'success' => false,
                'message' => 'No active production found to cancel.'
            ];
        }

        try {
            DB::transaction(function () use ($user, $queueEntry) {
                $details = $queueEntry->details;
                if (is_string($details)) {
                    $details = json_decode($details, true);
                }
                $quantity = $details['quantity'] ?? 1;

                $spacecraft = $this->spacecraftRepository->findSpacecraftById($queueEntry->target_id, $user->id);
                if (!$spacecraft) {
                    throw new \Exception('Spacecraft not found for cancellation.');
                }

                $totalCosts = $this->getSpacecraftsProductionCosts($spacecraft, $quantity);

                // 80% der Ressourcen zurückerstatten
                $refundCosts = $totalCosts->map(function ($cost) {
                    return [
                        'id' => $cost['id'],
                        'name' => $cost['name'],
                        'amount' => (int)floor($cost['amount'] * 0.8)
                    ];
                })->filter(fn($cost) => $cost['amount'] > 0)->values();

                foreach ($refundCosts as $cost) {
                    $this->userResourceService->addResourceAmount($user, $cost['id'], $cost['amount']);
                }

                // Produktion aus der Queue entfernen
                $this->queueService->deleteFromQueue($queueEntry->id);
            });

            broadcast(new UpdateUserResources($this->userService->find($user->id)));
            return [
                'success' => true,
                'message' => 'Production successfully canceled. 80% of resources have been refunded.'
            ];
        } catch (\Exception $e) {
            Log::error("Error occurred while canceling spacecraft production:", [
                'user_id' => $user->id,
                'spacecraft_id' => $spacecraft->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Error occurred while canceling production: ' . $e->getMessage()
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

    private function validateCrewCapacity(int $userId, int $requiredSpacecraftId, int $requiredQuantity): void
    {
        // Crew-Limit des Users holen
        $crewLimitAttr = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::CREW_LIMIT);
        if (!$crewLimitAttr) {
            throw new InsufficientCrewCapacityException();
        }
        $crewLimit = (int)$crewLimitAttr->attribute_value;
    
        // Alle Schiffe des Users holen
        $allSpacecrafts = $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetails($userId);
    
        // Crewbedarf aller Schiffe berechnen (inkl. Queue)
        $queueEntries = $this->queueService->getUserQueue($userId);
    
        // Map: spacecraft_id => queued_quantity
        $queuedQuantities = [];
        foreach ($queueEntries as $entry) {
            $actionType = $entry->action_type ?? $entry->actionType ?? null;
            $status = $entry->status ?? $entry->Status ?? null;
            if (
                $actionType === QueueActionType::ACTION_TYPE_PRODUCE &&
                in_array($status, [QueueStatusType::STATUS_IN_PROGRESS, QueueStatusType::STATUS_PENDING])
            ) {
                $details = is_string($entry->details) ? json_decode($entry->details, true) : $entry->details;
                $quantity = $details['quantity'] ?? 1;
                $spacecraftId = $entry->targetId ?? $entry->target_id ?? null;
                if ($spacecraftId) {
                    $queuedQuantities[$spacecraftId] = ($queuedQuantities[$spacecraftId] ?? 0) + (int)$quantity;
                }
            }
        }
    
        // Jetzt die Crew für alle Schiffe berechnen (inkl. der neuen Produktion)
        $totalCrew = 0;
        foreach ($allSpacecrafts as $sc) {
            $count = $sc->count;
            $queued = $queuedQuantities[$sc->id] ?? 0;
            // Wenn es das aktuell zu bauende Schiff ist, die neue Menge addieren
            if ($sc->id == $requiredSpacecraftId) {
                $queued += $requiredQuantity;
            }
            $totalCrew += $sc->crew_limit * ($count + $queued);
        }
    
        if ($totalCrew > $crewLimit) {
            throw new InsufficientCrewCapacityException();
        }
    }

    private function addSpacecraftUpgradeToQueue(int $userId, Spacecraft $spacecraft, int $quantity, int $targetQuantity): void
    {
        $shipyardBuilding = Building::where('user_id', $userId)
        ->whereHas('details', function ($query) {
            $query->where('name', BuildingType::SHIPYARD->value);
        })
        ->first();

        $extra = app(BuildingEffectService::class)->getEffects('Shipyard', $shipyardBuilding->level);
        $productionSlots = $extra['production_slots'] ?? 1;
        
        $build_time = $this->calculateSpacecraftBuildTime($userId, $spacecraft, $quantity);

        $this->queueService->addSpacecraftToQueue(
            $userId,
            $spacecraft->id,
            $build_time,
            [
                'spacecraft_name' => $spacecraft->details->name,
                'current_quantity' => $targetQuantity - $quantity,
                'next_quantity' => $targetQuantity,
                'quantity' => $quantity,
                'duration' => $build_time,
                'production_slots' => $productionSlots
            ],
            $productionSlots
        );
    }

    public function calculateSpacecraftBuildTime(int $userId, Spacecraft $spacecraft, int $quantity): float
    {
        $shipyard_production_speed = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::PRODUCTION_SPEED);
        $spacecraft_produce_speed = config('game.core.spacecraft_produce_speed');

        $production_multiplier = $shipyard_production_speed ? $shipyard_production_speed->attribute_value : 1;
        return floor((($spacecraft->build_time * $quantity) / $production_multiplier) / $spacecraft_produce_speed);
    }

    public function unlockSpacecraft(int $userId, Spacecraft $spacecraft): array
    {
        try {
            DB::transaction(function () use ($userId, $spacecraft) {
                $spacecraft->unlocked = true;
                $spacecraft->save();

                $this->influenceService->handleResearchUnlock($userId, $spacecraft->research_cost);
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
