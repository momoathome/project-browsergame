<?php

namespace Orion\Modules\Asteroid\Services;

use App\Events\UpdateUserResources;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\ReloadFrontendCanvas;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Asteroid\Dto\ExplorationResult;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Asteroid\Repositories\AsteroidRepository;
use Orion\Modules\Asteroid\Http\Requests\AsteroidExploreRequest;

class AsteroidService
{
    public function __construct(
        private readonly AsteroidRepository $asteroidRepository,
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly ActionQueueService $queueService,
        private readonly SpacecraftService $spacecraftService,
        private readonly StationService $stationService,
        private readonly UserService $userService,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService
    ) {
    }

    public function loadAsteroidWithResources(Asteroid $asteroid): Asteroid
    {
        return $this->asteroidRepository->loadWithResources($asteroid);
    }

    public function getAsteroidMapData(
        $user,
        array|Collection $searchedAsteroids = [],
        array|Collection $searchedStations = [],
        ?Asteroid $selectedAsteroid = null
    ): array {
        $asteroids = $this->asteroidRepository->getAllAsteroids();
        $stations = $this->stationService->getAllStations();
        $spacecrafts = $this->spacecraftService->formatSpacecraftsForDisplay($user->id);

        return [
            'asteroids' => $asteroids,
            'searched_asteroids' => $searchedAsteroids,
            'searched_stations' => $searchedStations,
            'spacecrafts' => $spacecrafts,
            'stations' => $stations,
            'selected_asteroid' => $selectedAsteroid ?? null,
        ];
    }

    public function asteroidMining($user, AsteroidExploreRequest $request): bool
    {
        if (!$request->validated()) {
            return false;
        }

        return $this->processMiningOperation(
            $user,
            $request->integer('asteroid_id'),
            $request->collect('spacecrafts')
        );
    }

    private function processMiningOperation(User $user, int $asteroidId, Collection $spaceCrafts): bool
    {
        return DB::transaction(function () use ($user, $asteroidId, $spaceCrafts) {
            $filteredSpacecrafts = $this->spacecraftService->filterSpacecrafts($spaceCrafts);
            $asteroid = $this->asteroidRepository->find($asteroidId);

            if (!$asteroid) {
                return false;
            }

            $spacecraftsWithDetails = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails(
                $user->id,
                $filteredSpacecrafts
            );

            $duration = $this->asteroidExplorer->calculateTravelDuration(
                $spacecraftsWithDetails,
                $user,
                $asteroid,
                QueueActionType::ACTION_TYPE_MINING,
                $filteredSpacecrafts
            );

            $this->spacecraftService->lockSpacecrafts($user, $filteredSpacecrafts);

            $this->queueService->addToQueue(
                $user->id,
                QueueActionType::ACTION_TYPE_MINING,
                $asteroidId,
                $duration,
                [
                    'asteroid_name' => $asteroid->name,
                    'spacecrafts' => $filteredSpacecrafts
                ]
            );

            return true;
        });
    }

    public function completeAsteroidMining(int $asteroidId, int $userId, array $details)
    {
        $user = $this->userService->find($userId);
        if (!$user) {
            return false;
        }

        $filteredSpacecrafts = $this->spacecraftService->filterSpacecrafts($details['spacecrafts']);

        // if asteroid is not found, free spacecrafts and return false
        $asteroid = $this->asteroidRepository->find($asteroidId);
        if (!$asteroid) {
            $this->spacecraftService->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        list($totalCargoCapacity, $hasMiner) = $this->asteroidExplorer->calculateCapacityAndMinerStatus(
            $user,
            $filteredSpacecrafts
        );

        $asteroidResources = $this->asteroidRepository->getAsteroidResources($asteroid);

        list($resourcesExtracted, $remainingResources) = $this->asteroidExplorer->calculateResourceExtraction(
            $asteroidResources,
            $totalCargoCapacity,
            $hasMiner
        );

        $transactionResult = DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted, $filteredSpacecrafts) {
            $this->updateUserResources($user, $resourcesExtracted);
            $this->asteroidRepository->updateAsteroidResources($asteroid, $remainingResources);
            $this->spacecraftService->freeSpacecrafts($user, $filteredSpacecrafts);

            return true;
        });

        if (!$transactionResult) {
            $this->spacecraftService->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        broadcast(new ReloadFrontendCanvas($asteroid));

        return new ExplorationResult(
            $resourcesExtracted,
            $totalCargoCapacity,
            $asteroid->id,
            $hasMiner
        );
    }

    public function updateUserResources(User $user, array $resourcesExtracted): void
    {
        $storageAttribute = $this->userAttributeService->getSpecificUserAttribute(
            $user->id,
            UserAttributeType::STORAGE
        );

        $storageCapacity = $storageAttribute->attribute_value;

        foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
            $userResource = $this->userResourceService->getSpecificUserResource(
                $user->id,
                $resourceId
            );

            $currentAmount = $userResource ? $userResource->amount : 0;
            $availableStorage = $storageCapacity - $currentAmount;
            $amountToAdd = min($extractedAmount, $availableStorage);

            if ($userResource) {
                $this->userResourceService->addResourceAmount($user, $resourceId, $amountToAdd);
            } else {
                $this->userResourceService->createUserResource($user->id, $resourceId, $amountToAdd);
            }
        }

        broadcast(new UpdateUserResources($user));
    }

    public function find(int $id): ?Asteroid
    {
        return $this->asteroidRepository->find($id);
    }

    public function getAllAsteroids(): Collection
    {
        return $this->asteroidRepository->getAllAsteroids();
    }
}
