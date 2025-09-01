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

        Log::info('AutoMineService: Übersicht', [
            'storageAttr'   => $storageAttr,
            'storageLimit'  => $storageLimit,
            'scanRange'     => $scanRange,
            'asteroidCount' => count($asteroids),
            'resources'     => $resourceStorage,
            'spacecrafts'   => $availableSpacecrafts->toArray(),
        ]);

        switch ($filter) {
            case 'overflow':
                return $this->extractOverflow($asteroids, $availableSpacecrafts, $user);
            case 'smart':
                return $this->extractSmart($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit);
            case 'minimal':
            default:
                return $this->extractMinimal($asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit);
        }
    }

    // Overflow: Ignoriere Storage-Limit, extrahiere alles
    private function extractOverflow($asteroids, $availableSpacecrafts, $user): array
    {
        $missions = [];
        // Miner-Pool: Name => Anzahl, Cargo
        $minerPool = [];
        foreach ($availableSpacecrafts as $sc) {
            $name = $sc->details->name;
            $cargo = (int)($sc->cargo ?? $sc->details->cargo ?? 0);
            $count = ($sc->count ?? 1) - ($sc->locked_count ?? 0); // Nur freie Schiffe!
            if ($count <= 0) continue; // Keine freien Schiffe, überspringen
            $minerPool[$name] = [
                'count' => $count,
                'cargo' => $cargo,
            ];
        }
    
        foreach ($asteroids as $asteroid) {
            $totalResources = 0;
            foreach ($asteroid->resources as $resource) {
                $totalResources += $resource->amount;
            }
    
            $minerAssignment = [];
            $cargoAssigned = 0;
    
            // Miner zuweisen, bis alle Ressourcen abgebaut oder keine Miner mehr verfügbar
            foreach ($minerPool as $name => $data) {
                if ($data['count'] <= 0) continue;
                $cargoPerMiner = $data['cargo'];
                $neededMiner = min($data['count'], ceil(($totalResources - $cargoAssigned) / $cargoPerMiner));
                if ($neededMiner <= 0) continue;
                $minerAssignment[$name] = $neededMiner;
                $cargoAssigned += $neededMiner * $cargoPerMiner;
                $minerPool[$name]['count'] -= $neededMiner;
                if ($cargoAssigned >= $totalResources) break;
            }
    
            if ($cargoAssigned <= 0) continue; // Keine Miner mehr verfügbar
    
            // Ressourcen extrahieren (hier: alle Ressourcen des Asteroiden)
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
    
            // Abbruch, wenn keine Miner mehr verfügbar
            $totalMinerLeft = array_sum(array_column($minerPool, 'count'));
            if ($totalMinerLeft <= 0) break;
        }
    
        Log::info('Overflow-Missions', ['missions' => $missions]);
        return $missions;
    }

    // Minimal: Kein Overflow, wie bisher
    private function extractMinimal($asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit): array
    {
        /* $usedStoragePerResource = $resourceStorage;
        $missions = [];
        foreach ($asteroids as $asteroid) {
            $resources = $asteroid->resources;
            $neededCargo = $resources->sum('amount');
            $minerAssignment = $this->assignMiners($availableSpacecrafts, $neededCargo);
            $cargoAssigned = array_sum($minerAssignment);
            $resourcesExtracted = [];
            foreach ($resources as $resource) {
                $resourceId = $resource->resource_type;
                $currentAmount = $usedStoragePerResource[$resourceId] ?? 0;
                $availableStorage = $storageLimit - $currentAmount;
                $extractableAmount = min($resource->amount, $availableStorage, $cargoAssigned);
                if ($extractableAmount > 0) {
                    $resourcesExtracted[$resourceId] = $extractableAmount;
                    $cargoAssigned -= $extractableAmount;
                    $usedStoragePerResource[$resourceId] = $currentAmount + $extractableAmount;
                }
            }
            if (empty($resourcesExtracted)) break;
            $duration = 0; // Optional: Berechne Dauer wie gehabt
            $missions[] = [
                'asteroid' => $asteroid,
                'spacecrafts' => $minerAssignment,
                'resources' => $resourcesExtracted,
                'duration' => $duration,
            ];
            // Abbruch wenn keine Miner mehr verfügbar
            if (array_sum($minerAssignment) <= 0) break;
            // Prüfe, ob für irgendeine Ressource noch Platz ist
            $anyStorageLeft = false;
            foreach ($usedStoragePerResource as $amount) {
                if ($amount < $storageLimit) {
                    $anyStorageLeft = true;
                    break;
                }
            }
            if (!$anyStorageLeft) break;
        }
        return $missions; */
    }

    // Smart: Maximale Ressourcen, minimaler Overflow
    private function extractSmart($user, $asteroids, $availableSpacecrafts, $resourceStorage, $storageLimit): array
    {
       
    }

    private function getCargoForMiner(string $name, User $user): int
    {
        $spacecrafts = $this->spacecraftService->getAvailableSpacecraftsByUserIdWithDetails($user->id);
        foreach ($spacecrafts as $sc) {
            if ($sc->details->name === $name) {
                return (int)($sc->cargo ?? $sc->details->cargo ?? 0);
            }
        }
        // Fallback falls nicht gefunden
        return 0;
    }

}
