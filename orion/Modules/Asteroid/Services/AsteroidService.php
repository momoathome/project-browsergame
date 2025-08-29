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
use Orion\Modules\Asteroid\Services\AsteroidGenerator;


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
        $spacecrafts = $this->spacecraftService->formatSpacecraftsSimple($user->id);

        return [
            'asteroids' => $asteroids,
            'spacecrafts' => $spacecrafts,
            'stations' => $stations,
        ];
    }

    public function StartAsteroidMining($user, AsteroidExploreRequest $request): array
    {
        try {
            $asteroidId = $request->integer('asteroid_id');
            $spaceCrafts = $request->collect('spacecrafts');

            // Validiere Asteroid und Raumschiffe
            $asteroid = $this->validateMiningOperation($asteroidId, $user, $spaceCrafts);

            // Führe die Mining-Operation in einer Transaktion durch
            DB::transaction(function () use ($user, $asteroid, $spaceCrafts) {
                // Raumschiffe filtern und sperren
                $filteredSpacecrafts = $this->spacecraftService->filterSpacecrafts($spaceCrafts);
                $this->spacecraftService->lockSpacecrafts($user, $filteredSpacecrafts);

                // Reisedauer berechnen
                $duration = $this->calculateMiningDuration($user, $asteroid, $filteredSpacecrafts);

                // Zur Queue hinzufügen
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

    private function validateMiningOperation(int $asteroidId, User $user, Collection $spaceCrafts): Asteroid
    {
        $asteroid = $this->find($asteroidId);

        if (!$asteroid) {
            throw new \Exception("Asteroid not found");
        }

        if ($spaceCrafts->isEmpty()) {
            throw new \Exception("No spacecrafts selected");
        }

        // Prüfe, ob der User genug freie (nicht gelockte) Spacecrafts besitzt
        // Hole alle Spacecrafts des Users inkl. locked_count
        $userSpacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($user->id);
        // Erstelle ein Array: [name => [total, locked]] (Name aus details-Relation)
        $spacecraftStatus = [];
        foreach ($userSpacecrafts as $sc) {
            $type = $sc->details->name ?? $sc->details_id; // Fallback auf ID, falls Name nicht geladen
            $spacecraftStatus[$type] = [
                'total' => $sc->count,
                'locked' => $sc->locked_count,
            ];
        }

        // Für jeden Typ, der versendet werden soll, prüfen ob genug unlocked vorhanden sind
        foreach ($spaceCrafts as $type => $amount) {
            if ($amount <= 0) continue;
            if (!isset($spacecraftStatus[$type])) {
                throw new \Exception("User does not own spacecraft type: $type");
            }
            $available = $spacecraftStatus[$type]['total'] - $spacecraftStatus[$type]['locked'];
            if ($available < $amount) {
                throw new \Exception("Not enough unlocked $type available. Requested: $amount, Available: $available");
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

        try {
            $asteroidGenerator = app(AsteroidGenerator::class);
            $asteroidGenerator->generateAsteroids(rand(1, 3)); // oder eine Zufallszahl
        } catch (\Exception $e) {
            Log::error('Fehler beim Generieren eines neuen Asteroiden: ' . $e->getMessage());
        }

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
