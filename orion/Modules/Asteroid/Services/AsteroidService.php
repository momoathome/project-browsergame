<?php

namespace Orion\Modules\Asteroid\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Illuminate\Support\Facades\Log;
use App\Events\ReloadFrontendCanvas;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Asteroid\Dto\ExplorationResult;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Building\Services\BuildingEffectService;
use Orion\Modules\Asteroid\Repositories\AsteroidRepository;
use Orion\Modules\Asteroid\Http\Requests\AsteroidExploreRequest;
use Orion\Modules\Rebel\Services\RebelService;


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
        private readonly UserAttributeService $userAttributeService,
        private readonly RebelService $rebelService,
    ) {
    }

    public function loadAsteroidWithResources(Asteroid $asteroid): Asteroid
    {
        return $this->asteroidRepository->loadWithResources($asteroid);
    }

    public function getAsteroidMapData($user): array
    {
        $asteroids = $this->asteroidRepository->getAllAsteroids();
        $stations = $this->stationService->getAllStations();
        $influenceOfAllUsers = $this->userAttributeService->getInfluenceOfAllUsers();
        $rebels = $this->rebelService->getAllRebels();

        return [
            'asteroids' => $asteroids,
            'stations' => $stations,
            'influenceOfAllUsers' => $influenceOfAllUsers,
            'rebels' => $rebels,
        ];
    }

    public function startAsteroidMining($user, AsteroidExploreRequest $request): array
    {
        try {
            $asteroidId = $request->integer('asteroid_id');
            $spaceCrafts = $request->collect('spacecrafts');
            $asteroid = $this->find($asteroidId);

            DB::transaction(function () use ($user, $asteroid, $spaceCrafts) {
                // Validiere Asteroid und Raumschiffe
                $asteroid = $this->validateMiningOperation($asteroid, $user, $spaceCrafts);
                // Raumschiffe filtern und sperren
                $filteredSpacecrafts = $this->spacecraftService->filterSpacecrafts($spaceCrafts);
                $this->spacecraftService->lockSpacecrafts($user, $filteredSpacecrafts);

                $duration = $this->calculateMiningDuration($user, $asteroid, $filteredSpacecrafts);
                $this->addMiningOperationToQueue($user, $asteroid, $duration, $filteredSpacecrafts);

            });

            return [
                'success' => true,
                'message' => "Mining operation for asteroid {$asteroid->name} successfully started.",
            ];

        } catch (\Exception $e) {
            Log::error('Mining operation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => "Mining operation for asteroid {$asteroid->name} failed.",
            ];
        }
    }

    private function validateMiningOperation(Asteroid $asteroid, User $user, Collection $spaceCrafts): Asteroid
    {
        if (!$asteroid) {
            throw new \Exception("Asteroid not found");
        }

        if ($spaceCrafts->isEmpty()) {
            throw new \Exception("No spacecrafts selected");
        }

        $hangarBuilding = Building::where('user_id', $user->id)
        ->whereHas('details', function ($query) {
            $query->where('name', BuildingType::HANGAR->value);
        })
        ->first();

        $dockSlots = 1;
        if ($hangarBuilding) {
            $extra = app(BuildingEffectService::class)->getEffects('Hangar', $hangarBuilding->level);
            $dockSlots = $extra['dock_slots'] ?? 1;
        }

        $currentMiningOperations = $this->queueService->getInProgressQueuesFromUserByType(
            $user->id,
            QueueActionType::ACTION_TYPE_MINING
        )->count();

        if ($currentMiningOperations >= $dockSlots) {
            throw new \Exception("Not enough dock slots available. Current: $currentMiningOperations, Max: $dockSlots");
        }

        // Hole alle verfügbaren (nicht gelockten) Spacecrafts des Users
        $availableSpacecrafts = $this->spacecraftService->getAvailableSpacecraftsByUserIdWithDetails($user->id);
        
        // Erstelle ein Array: [name => available_count]
        $availableStatus = [];
        foreach ($availableSpacecrafts as $sc) {
            $type = $sc->details->name ?? $sc->details_id;
            $availableStatus[$type] = $sc->available_count;
        }
        
        // Für jeden Typ, der versendet werden soll, prüfen ob genug verfügbar sind
        foreach ($spaceCrafts as $type => $amount) {
            if ($amount <= 0) continue;
            if (!isset($availableStatus[$type])) {
                throw new \Exception("User does not own spacecraft type: $type");
            }
            if ($availableStatus[$type] < $amount) {
                throw new \Exception("Not enough unlocked $type available. Requested: $amount, Available: {$availableStatus[$type]}");
            }
        }

        return $asteroid;
    }

    private function calculateMiningDuration(User $user, Asteroid $asteroid, Collection $filteredSpacecrafts): int
    {
        $spacecraftsWithDetails = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails(
            $user->id,
            $filteredSpacecrafts
        );

        return $this->asteroidExplorer->calculateTravelDuration(
            $spacecraftsWithDetails,
            $user,
            $asteroid,
            QueueActionType::ACTION_TYPE_MINING,
            $filteredSpacecrafts
        );
    }

    private function addMiningOperationToQueue(User $user, Asteroid $asteroid, int $duration, Collection $filteredSpacecrafts): void
    {
        $this->queueService->addToQueue(
            $user->id,
            QueueActionType::ACTION_TYPE_MINING,
            $asteroid->id,
            $duration,
            [
                'asteroid_name' => $asteroid->name,
                'spacecrafts' => $filteredSpacecrafts,
                'target_coordinates' => [
                    'x' => $asteroid->x,
                    'y' => $asteroid->y,
                ],
            ]
        );
    }

    public function completeAsteroidMining(int $asteroidId, int $userId, array $details)
    {
        $user = $this->userService->find($userId);
        if (!$user) {
            return false;
        }

        $filteredSpacecrafts = $this->spacecraftService->filterSpacecrafts($details['spacecrafts']);

        // if asteroid is not found, free spacecrafts and return false
        $asteroid = $this->find($asteroidId);
        if (!$asteroid) {
            $this->spacecraftService->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        list($totalCargoCapacity, $hasMiner, $hasTitan) = $this->asteroidExplorer->calculateCapacityAndMinerStatus(
            $user,
            $filteredSpacecrafts
        );

        $asteroidResources = $this->asteroidRepository->getAsteroidResources($asteroid);

        // Prüfe auf extreme Asteroiden und Massive Miner ("Titan")
        if ($asteroid->size === 'extreme' && !$hasTitan) {
            $resourcesExtracted = [];
            $remainingResources = [];
            foreach ($asteroidResources as $asteroidResource) {
                $remainingResources[$asteroidResource->resource_type] = $asteroidResource->amount;
            }
        } else {
            list($resourcesExtracted, $remainingResources) = $this->asteroidExplorer->calculateResourceExtraction(
                $asteroidResources,
                $totalCargoCapacity,
                $hasMiner
            );
        }

        try {
            DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted, $filteredSpacecrafts) {
                $this->updateUserResources($user, $resourcesExtracted);
                $this->asteroidRepository->updateAsteroidResources($asteroid, $remainingResources);
                $this->spacecraftService->freeSpacecrafts($user, $filteredSpacecrafts);
            });
        } catch (\Throwable $e) {
            Log::error('AsteroidMining DB-Transaktion fehlgeschlagen', [
                'asteroidId' => $asteroidId,
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
            // Cleanup: Versuche Raumschiffe zu entsperren, falls sie gelockt sind
            $this->spacecraftService->freeSpacecrafts($user, $filteredSpacecrafts);
            return false;
        }

        // TODO: Refactor this into a dedicated service with live broadcasting
        try {
            $asteroidGenerator = app(AsteroidGenerator::class);
            $radius = 15000;
            $asteroidGenerator->generateAsteroids(
                rand(0, 2),
                $asteroid->x,
                $asteroid->y,
                $radius
            );
        } catch (\Exception $e) {
            Log::error('Fehler beim Generieren eines neuen Asteroiden: ' . $e->getMessage());
        }

        // removes mined asteroid from map in real-time
        broadcast(new ReloadFrontendCanvas($asteroid));

        $this->asteroidRepository->saveAsteroidMiningResult(
            $user,
            $asteroid,
            new ExplorationResult(
                $resourcesExtracted,
                $totalCargoCapacity,
                $asteroid->id,
                $hasMiner
            ),
            $filteredSpacecrafts
        );

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

    public function saveAsteroidMiningResult(User $user, Asteroid $asteroid, ExplorationResult $result, $filteredSpacecrafts): void
    {
        $this->asteroidRepository->saveAsteroidMiningResult($user, $asteroid, $result, $filteredSpacecrafts);
    }
}
