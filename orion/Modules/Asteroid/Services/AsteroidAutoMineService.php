<?php

namespace Orion\Modules\Asteroid\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Asteroid\Services\AsteroidService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Building\Services\BuildingEffectService;
use Orion\Modules\Asteroid\Repositories\AsteroidRepository;
use Orion\Modules\Asteroid\Http\Requests\AsteroidExploreRequest;
use Orion\Modules\Spacecraft\Services\SpacecraftLockService;

class AsteroidAutoMineService
{
    public function __construct(
        private readonly AsteroidRepository $asteroidRepository,
        private readonly AsteroidService $asteroidService,
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService,
        private readonly SpacecraftService $spacecraftService,
        private readonly StationService $stationService,
        private readonly ActionQueueService $queueService,
        private readonly SpacecraftLockService $spacecraftLockService,
    ) {}

    public function prepareAutoMineMissions(User $user, string $filter = 'overflow'): array
    {
        $station = $this->stationService->findStationByUserId($user->id);
        $storageAttr = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::STORAGE);
        $storageLimit = $storageAttr ? (int)$storageAttr->attribute_value : 0;

        $scanRange = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::SCAN_RANGE)?->attribute_value ?? 5000;
        $asteroids = $this->asteroidRepository->getAsteroidsInRange($station->x, $station->y, $scanRange);

        // Filtere Asteroiden, die bereits abgebaut werden
        $activeMiningQueues = $this->queueService
            ->getInProgressQueuesFromUserByType($user->id, QueueActionType::ACTION_TYPE_MINING)
            ->pluck('target_id')
            ->toArray();

        $asteroids = $asteroids->filter(fn($a) => !in_array($a->id, $activeMiningQueues));
        $asteroids = $asteroids->sortBy(fn($a) => sqrt(pow($station->x - $a->x, 2) + pow($station->y - $a->y, 2)));

        $userResources = $this->userResourceService->getAllUserResourcesByUserId($user->id);
        $resourceStorage = [];
        foreach ($userResources as $userResource) {
            $resourceStorage[$userResource->resource_id] = $userResource->amount;
        }

        $miningSpacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($user->id)
            ->filter(fn($sc) => $sc->details->type === 'Miner' || $sc->details->name === 'Titan');

        $locks = $this->spacecraftLockService->getLocksForUser($user->id);

        $lockedCounts = [];
        foreach ($locks as $lock) {
            $lockedCounts[$lock->spacecraft_details_id] = ($lockedCounts[$lock->spacecraft_details_id] ?? 0) + $lock->amount;
        }

        $availableSpacecrafts = [];
        foreach ($miningSpacecrafts as $sc) {
            $detailsId = $sc->details->id;
            $locked = $lockedCounts[$detailsId] ?? 0;
            $available = max(0, $sc->count - $locked);

            $availableSpacecrafts[$sc->details->name] = [
                'count' => $available,
                'cargo' => (int)($sc->cargo ?? 0),
                'speed' => $sc->speed ?? 1,
                'operation_speed' => $sc->operation_speed ?? 1,
                'locked_count' => $locked,
                'total_count' => $sc->count,
                'details' => $sc->details,
            ];
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

        switch ($filter) {
            case 'overflow':
                return $this->extractOverflow($asteroids, $availableSpacecrafts, $user, $dockSlots, $currentMiningOperations);
            case 'smart':
                return $this->extractSmart($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit);
            case 'minimal':
            default:
                return $this->extractMinimal($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit);
        }
    }

    private function buildMinerPool($availableSpacecrafts): array
    {
        $minerPool = [];
        foreach ($availableSpacecrafts as $name => $sc) {
            $cargo = (int)($sc['cargo'] ?? 0);
            $count = $sc['count'] ?? 0;
            if ($count <= 0) continue;
            $minerPool[$name] = [
                'count' => $count,
                'cargo' => $cargo,
            ];
        }
        return $minerPool;
    }

    private function assignMinersToAsteroid(array &$minerPool, $asteroid, int $totalResources, bool $isExtreme = false, bool $isSmall = false): array
    {
        $minerAssignment = [];
        $cargoAssigned = 0;

        // Falls extrem und kein Titan verfügbar: Keine Miner zuweisen!
        if ($isExtreme && (!isset($minerPool['Titan']) || $minerPool['Titan']['count'] <= 0)) {
            return [$minerAssignment, $cargoAssigned];
        }

        // Zuerst: Mindestens ein Titan zuweisen, falls extrem und Titan verfügbar
        if ($isExtreme && isset($minerPool['Titan']) && $minerPool['Titan']['count'] > 0) {
            $minerAssignment['Titan'] = 1;
            $cargoAssigned += $minerPool['Titan']['cargo'];
            $minerPool['Titan']['count'] -= 1;
        }

        foreach ($minerPool as $name => $data) {
            if ($data['count'] <= 0) continue;
            // Titans sollen keine "small"-Asteroiden minen
            if (strtolower($name) === 'titan' && $isSmall) continue;
            $cargoPerMiner = $data['cargo'];
            $neededMiner = min($data['count'], ceil(($totalResources - $cargoAssigned) / $cargoPerMiner));
            if ($neededMiner <= 0) continue;
            $minerAssignment[$name] = ($minerAssignment[$name] ?? 0) + $neededMiner;
            $cargoAssigned += $neededMiner * $cargoPerMiner;
            $minerPool[$name]['count'] -= $neededMiner;
            if ($cargoAssigned >= $totalResources) break;
        }

        return [$minerAssignment, $cargoAssigned];
    }

    public function startAutoMineMissions($user, array $missions): array
    {
        $results = [];
        // Nur einmal laden!
        $availableSpacecrafts = $this->spacecraftService->getAvailableSpacecraftsByUserIdWithDetails($user->id);

        foreach (array_chunk($missions, 20) as $missionBatch) {
            foreach ($missionBatch as $mission) {
                $asteroidId = $mission['asteroid_id'];
                $spacecrafts = collect($mission['spacecrafts']);

                // Prüfe, ob noch genug Raumschiffe verfügbar sind
                foreach ($spacecrafts as $type => $amount) {
                    $available = $availableSpacecrafts->first(fn($sc) => $sc->details->name === $type)?->available_count ?? 0;
                    if ($available < $amount) {
                        $results[] = [
                            'success' => false,
                            'message' => "Not enough $type available for asteroid $asteroidId.",
                        ];
                        continue 2; // nächste Mission
                    }
                }

                // Lokale Kopie aktualisieren (Raumschiffe abziehen)
                foreach ($spacecrafts as $type => $amount) {
                    $sc = $availableSpacecrafts->first(fn($sc) => $sc->details->name === $type);
                    if ($sc) {
                        $sc->available_count -= $amount;
                    }
                }

                $result = $this->asteroidService->startAsteroidMining($user, new AsteroidExploreRequest([
                    'asteroid_id' => $asteroidId,
                    'spacecrafts' => $spacecrafts,
                ]));
                $results[] = $result;
            }
        }
        usleep(1000000);

        return $results;
    }

    // Overflow: Ignoriere Storage-Limit, extrahiere alles
    private function extractOverflow($asteroids, $availableSpacecrafts, $user, $dockSlots = 1, $currentMiningOperations = 0): array
    {
        $missions = [];
        $minerPool = $this->buildMinerPool($availableSpacecrafts);
        $missionCount = $currentMiningOperations;
    
        foreach ($asteroids as $asteroid) {
            $totalResources = 0;
            foreach ($asteroid->resources as $resource) {
                $totalResources += $resource->amount;
            }
    
            $isExtreme = isset($asteroid->size) && strtolower($asteroid->size) === 'extreme';
            $isSmall = isset($asteroid->size) && strtolower($asteroid->size) === 'small';
    
            [$minerAssignment, $cargoAssigned] = $this->assignMinersToAsteroid($minerPool, $asteroid, $totalResources, $isExtreme, $isSmall);
    
            if ($cargoAssigned <= 0) continue;
    
            // Ressourcenmenge auf Cargo begrenzen!
            $resourcesExtracted = [];
            $remainingCargo = $cargoAssigned;
            foreach ($asteroid->resources as $resource) {
                $extractAmount = min($resource->amount, $remainingCargo);
                $resourcesExtracted[$resource->resource_type] = $extractAmount;
                $remainingCargo -= $extractAmount;
                if ($remainingCargo <= 0) break;
            }
            
            $missions[] = [
                'asteroid' => $asteroid,
                'spacecrafts' => $minerAssignment,
                'resources' => $resourcesExtracted,
                'duration' => $this->asteroidExplorer->calculateTravelDuration(
                    collect($availableSpacecrafts),
                    $user,
                    $asteroid,
                    QueueActionType::ACTION_TYPE_MINING,
                    collect($minerAssignment)
                ),
            ];
    
            $missionCount++;
            if ($missionCount >= $dockSlots) break;

            $totalMinerLeft = array_sum(array_column($minerPool, 'count'));
            if ($totalMinerLeft <= 0) break;
        }
    
        return $missions;
    }

    // Minimal: Kein Overflow, fill storage
    private function extractMinimal($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit): array
    {
        $usedStoragePerResource = $resourceStorage;
        $missions = [];
        $minerPool = $this->buildMinerPool($availableSpacecrafts);
    
        foreach ($asteroids as $asteroid) {
            $resourcesExtracted = [];
            $totalExtractable = 0;
            foreach ($asteroid->resources as $resource) {
                $resourceId = $resource->resource_type;
                $currentAmount = $usedStoragePerResource[$resourceId] ?? 0;
                $availableStorage = $storageLimit - $currentAmount;
                $extractableAmount = min($resource->amount, $availableStorage);
                if ($extractableAmount > 0) {
                    $resourcesExtracted[$resourceId] = $extractableAmount;
                    $totalExtractable += $extractableAmount;
                }
            }
            if ($totalExtractable <= 0) continue;
    
            $isExtreme = isset($asteroid->size) && strtolower($asteroid->size) === 'extreme';
            $isSmall = isset($asteroid->size) && strtolower($asteroid->size) === 'small';
    
            [$minerAssignment, $cargoAssigned] = $this->assignMinersToAsteroid($minerPool, $asteroid, $totalExtractable, $isExtreme, $isSmall);
    
            if ($cargoAssigned <= 0) continue;
    
            foreach ($resourcesExtracted as $resourceId => $amount) {
                $maxByCargo = min($amount, $cargoAssigned);
                $resourcesExtracted[$resourceId] = $maxByCargo;
                $cargoAssigned -= $maxByCargo;
                $usedStoragePerResource[$resourceId] = ($usedStoragePerResource[$resourceId] ?? 0) + $maxByCargo;
            }
    
            $missions[] = [
                'asteroid' => $asteroid,
                'spacecrafts' => $minerAssignment,
                'resources' => $resourcesExtracted,
                'duration' => $this->asteroidExplorer->calculateTravelDuration(
                    collect($availableSpacecrafts),
                    $user,
                    $asteroid,
                    QueueActionType::ACTION_TYPE_MINING,
                    collect($minerAssignment)
                ),
            ];
    
            $totalMinerLeft = array_sum(array_column($minerPool, 'count'));
            if ($totalMinerLeft <= 0) break;
    
            $anyStorageLeft = false;
            foreach ($usedStoragePerResource as $amount) {
                if ($amount < $storageLimit) {
                    $anyStorageLeft = true;
                    break;
                }
            }
            if (!$anyStorageLeft) break;
        }
        return $missions;
    }

    // Smart: Maximale Ressourcen, minimaler Overflow
    private function extractSmart($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit): array
    {
       //
    }

}
