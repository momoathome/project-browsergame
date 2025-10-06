<?php

namespace Orion\Modules\Asteroid\Services;

use App\Models\User;
use Illuminate\Support\Collection;
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

    public function prepareAutoMineMissions(User $user, string $filter = 'overflow'): Collection
    {
        $station = $this->stationService->findStationByUserId($user->id);
        $storageAttr = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::STORAGE);
        $storageLimit = $storageAttr ? (int) $storageAttr->attribute_value : 0;

        $scanRange = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::SCAN_RANGE)?->attribute_value ?? 5000;

        $asteroids = $this->asteroidRepository
            ->getAsteroidsInRange($station->x, $station->y, $scanRange);

        // IDs von Asteroiden, die bereits gemined werden
        $activeMiningQueues = $this->queueService
            ->getInProgressQueuesFromUserByType($user->id, QueueActionType::ACTION_TYPE_MINING)
            ->pluck('target_id');

        // Filter + Sortierung nach Distanz
        $asteroids = $asteroids
            ->reject(fn($a) => $activeMiningQueues->contains($a->id))
            ->sortBy(fn($a) => sqrt(pow($station->x - $a->x, 2) + pow($station->y - $a->y, 2)))
            ->values();

        $resourceStorage = $this->userResourceService
            ->getAllUserResourcesByUserId($user->id)
            ->mapWithKeys(fn($r) => [$r->resource_id => $r->amount]);

        $miningSpacecrafts = $this->spacecraftService
            ->getAllSpacecraftsByUserIdWithDetails($user->id)
            ->filter(fn($sc) => in_array($sc->details->type, ['Miner']) || $sc->details->name === 'Titan');
        
        $locks = $this->spacecraftLockService->getLocksForUser($user->id)
            ->groupBy('spacecraft_details_id')
            ->map(fn($g) => $g->sum('amount'));
        
        $availableSpacecrafts = collect();
        foreach ($miningSpacecrafts as $sc) {
            $locked = $locks[$sc->details->id] ?? 0;
            $available = max(0, $sc->count - $locked);
            if ($available > 0) {
                for ($i = 0; $i < $available; $i++) {
                    $availableSpacecrafts->push($sc);
                }
            }
        }

        $dockSlots = $this->asteroidService->getDockSlotsForUser($user);
        $currentMiningOperations = $this->queueService
            ->getInProgressQueuesFromUserByType($user->id, QueueActionType::ACTION_TYPE_MINING)
            ->count();

        return match ($filter) {
            'overflow' => $this->extractOverflow($asteroids, $availableSpacecrafts, $user, $dockSlots, $currentMiningOperations),
            'smart'    => $this->extractSmart($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit),
            'minimal'  => $this->extractMinimal($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit),
            default    => collect(),
        };
    }

    private function buildMinerPool(Collection $availableSpacecrafts): Collection
    {
        return $availableSpacecrafts->mapWithKeys(function ($sc) {
            if ($sc->count <= 0) {
                return [];
            }

            return [
                $sc->details->name => [
                    'model' => $sc, 
                    'count' => $sc->count,
                    'cargo' => $sc->cargo,
                    'speed' => $sc->speed,
                    'operation_speed' => $sc->operation_speed,
                ]
            ];
        });
    }

    private function assignMinersToAsteroid(Collection &$minerPool, $asteroid, int $totalResources, bool $isExtreme = false, bool $isSmall = false): Collection
    {
        $assignments = collect();
        $cargoAssigned = 0;

        // Extrem-Asteroid benötigt Titan
        if ($isExtreme && (!$minerPool->has('Titan') || $minerPool['Titan']['count'] <= 0)) {
            return collect();
        }

        // Titan forcieren
        if ($isExtreme && $minerPool->has('Titan') && $minerPool['Titan']['count'] > 0) {
            $assignments->push([
                'name' => 'Titan',
                'count' => 1,
                'cargo' => $minerPool['Titan']['cargo'],
                'speed' => $minerPool['Titan']['speed'],
                'operation_speed' => $minerPool['Titan']['operation_speed'],
            ]);
            $cargoAssigned += $minerPool['Titan']['cargo'];
            $minerPool['Titan']['count']--;
        }

        foreach ($minerPool as $name => &$data) {
            if ($data['count'] <= 0) continue;
            if (strtolower($name) === 'titan' && $isSmall) continue;

            $needed = min($data['count'], ceil(($totalResources - $cargoAssigned) / $data['cargo']));
            if ($needed <= 0) continue;

            $assignments->push([
                'name' => $name,
                'count' => $needed,
                'cargo' => $data['cargo'],
                'speed' => $data['speed'],
                'operation_speed' => $data['operation_speed'],
                'model' => $data['model'],
            ]);

            $cargoAssigned += $needed * $data['cargo'];
            $minerPool->put($name, array_merge($data, [
                'count' => $data['count'] - $needed
            ]));

            if ($cargoAssigned >= $totalResources) break;
        }

        return $assignments;
    }

    private function extractOverflow(Collection $asteroids, Collection $availableSpacecrafts, User $user, int $dockSlots, int $currentMiningOperations): Collection
    {
        $missions = collect();
        $minerPool = $this->buildMinerPool($availableSpacecrafts);
        $missionCount = $currentMiningOperations;

        foreach ($asteroids as $asteroid) {
            $totalResources = $asteroid->resources->sum('amount');

            $isExtreme = strtolower($asteroid->size ?? '') === 'extreme';
            $isSmall = strtolower($asteroid->size ?? '') === 'small';

            $assignments = $this->assignMinersToAsteroid($minerPool, $asteroid, $totalResources, $isExtreme, $isSmall);

            if ($assignments->isEmpty()) continue;

            // extrahierte Ressourcen berechnen
            $remainingCargo = $assignments->sum(fn($a) => $a['count'] * $a['cargo']);
            $resourcesExtracted = $asteroid->resources->mapWithKeys(function ($res) use (&$remainingCargo) {
                $extractAmount = min($res->amount, $remainingCargo);
                $remainingCargo -= $extractAmount;
                return [$res->resource_type => $extractAmount];
            });

            $missionSpacecrafts = $assignments->pluck('model')->filter();

            $missions->push([
                'asteroid' => $asteroid,
                'spacecrafts' => $assignments,
                'resources' => $resourcesExtracted,
                'duration' => $this->asteroidExplorer->calculateTravelDuration(
                    $missionSpacecrafts,
                    $user,
                    $asteroid,
                    QueueActionType::ACTION_TYPE_MINING,
                    $assignments->mapWithKeys(fn($a) => [$a['name'] => $a['count']])
                )
            ]);

            if (++$missionCount >= $dockSlots) break;
            if ($minerPool->sum('count') <= 0) break;
        }

        return $missions;
    }

    // Minimal: Kein Overflow, fill storage
    private function extractMinimal($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit)
    {
        //
    }

    // Smart: Maximale Ressourcen, minimaler Overflow
    private function extractSmart($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit)
    {
       //
    }

}
