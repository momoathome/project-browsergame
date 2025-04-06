<?php

namespace Orion\Modules\Spacecraft\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;
use Orion\Modules\Resource\Exceptions\InsufficientResourceException;
use Orion\Modules\Spacecraft\Exceptions\InsufficientCrewCapacityException;


class SpacecraftProductionService
{
    public function __construct(
        private readonly ActionQueueService $queueService,
        private readonly SpacecraftRepository $spacecraftRepository,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService,
        private readonly ResourceService $resourceService,
        private readonly UserService $userService
    ) {
    }

    public function startSpacecraftProduction(User $user, Spacecraft $spacecraft, int $quantity): array
    {
        try {
            DB::transaction(function () use ($user, $spacecraft, $quantity) {
                $totalCosts = $this->getSpacecraftsProductionCosts($spacecraft, $quantity);

                $this->validateCrewCapacity($user->id, $quantity);

                $this->userResourceService->validateUserHasEnoughResources($user->id, $totalCosts);

                $this->userResourceService->decrementUserResources($user, $totalCosts);

                $this->addSpacecraftUpgradeToQueue($user->id, $spacecraft, $quantity);
            });

            broadcast(new UpdateUserResources($user));
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
                'user_id' => $user->id,
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

    private function validateCrewCapacity(int $userId, int $requiredCapacity): void
    {
        $crewLimit = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::CREW_LIMIT);

        if (!$crewLimit || $crewLimit->attribute_value < $requiredCapacity) {
            throw new InsufficientCrewCapacityException();
        }
    }

    private function addSpacecraftUpgradeToQueue(int $userId, Spacecraft $spacecraft, int $quantity): void
    {
        $shipyard_production_speed = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::PRODUCTION_SPEED);
        $spacecraft_produce_speed = config('game.core.spacecraft_produce_speed');

        $production_multiplier = $shipyard_production_speed ? $shipyard_production_speed->attribute_value : 1;

        $this->queueService->addToQueue(
            $userId,
            QueueActionType::ACTION_TYPE_PRODUCE,
            $spacecraft->id,
            floor((($spacecraft->build_time * $quantity) / $production_multiplier) / $spacecraft_produce_speed),
            [
                'spacecraft_name' => $spacecraft->details->name,
                'quantity' => $quantity,
            ]
        );
    }

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
