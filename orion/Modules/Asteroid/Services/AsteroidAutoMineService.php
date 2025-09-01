<?php

namespace Orion\Modules\Asteroid\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use \Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Asteroid\Repositories\AsteroidRepository;
use Orion\Modules\User\Services\UserResourceService;

class AsteroidAutoMineService
{
    public function __construct(
        private readonly AsteroidRepository $asteroidRepository,
        private readonly SpacecraftService $spacecraftService,
        private readonly UserAttributeService $userAttributeService,
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly StationService $stationService,
        private readonly UserResourceService $userResourceService,
    ) {}

    public function prepareAutoMineMissions(User $user, string $filter = 'smart'): array
    {
        $station = $this->stationService->findStationByUserId($user->id);
        $storageAttr = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::STORAGE);
        $storageLimit = $storageAttr ? (int)$storageAttr->attribute_value : 0;

        $scanRange = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::SCAN_RANGE)?->attribute_value ?? 5000;
        $asteroids = $this->asteroidRepository->getAsteroidsInRange($station->x, $station->y, $scanRange);

        $userResources = $this->userResourceService->getAllUserResourcesByUserId($user->id);
        $resourceStorage = [];
        foreach ($userResources as $userResource) {
            $resourceStorage[$userResource->resource_id] = $userResource->amount;
        }

        $availableSpacecrafts = $this->spacecraftService->getAvailableSpacecraftsByUserIdWithDetails($user->id)
            ->filter(fn($sc) => $sc->details->type === 'Miner' || strtolower($sc->details->name) === 'titan');

        $asteroids = $asteroids->sortBy(fn($a) => sqrt(pow($station->x - $a->x, 2) + pow($station->y - $a->y, 2)));

        switch ($filter) {
            case 'overflow':
                return $this->extractOverflow($asteroids, $availableSpacecrafts, $user);
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
        foreach ($availableSpacecrafts as $sc) {
            $name = $sc->details->name;
            $cargo = (int)($sc->cargo ?? 0);
            $count = ($sc->count ?? 1) - ($sc->locked_count ?? 0);
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

    // Overflow: Ignoriere Storage-Limit, extrahiere alles
    private function extractOverflow($asteroids, $availableSpacecrafts, $user): array
    {
        $missions = [];
        $minerPool = $this->buildMinerPool($availableSpacecrafts);
    
        foreach ($asteroids as $asteroid) {
            $totalResources = 0;
            foreach ($asteroid->resources as $resource) {
                $totalResources += $resource->amount;
            }
    
            $isExtreme = isset($asteroid->size) && strtolower($asteroid->size) === 'extreme';
            $isSmall = isset($asteroid->size) && strtolower($asteroid->size) === 'small';
    
            [$minerAssignment, $cargoAssigned] = $this->assignMinersToAsteroid($minerPool, $asteroid, $totalResources, $isExtreme, $isSmall);
    
            if ($cargoAssigned <= 0) continue;
    
            $resourcesExtracted = [];
            foreach ($asteroid->resources as $resource) {
                $resourcesExtracted[$resource->resource_type] = $resource->amount;
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
       
    }

}
