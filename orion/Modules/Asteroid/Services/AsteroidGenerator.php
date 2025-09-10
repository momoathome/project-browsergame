<?php

namespace Orion\Modules\Asteroid\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Models\AsteroidResource;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Asteroid\Repositories\AsteroidRepository;

class AsteroidGenerator
{
    public function __construct(
        private readonly StationService $stationService,
        private readonly AsteroidRepository $asteroidRepository,
    ) {
        $this->asteroidConfig = config('game.asteroids');
        $this->config = config('game.core');
        $this->stations = $this->stationService->getAllStations()->toArray();
    }

    private array $asteroidConfig = [];
    private array $config = [];
    private array $stations = [];

    public function generateAsteroids($count, $centerX = null, $centerY = null, $radius = null)
    {
        $asteroids = [];
        $maxFailures = $count * 0.1;
        $failures = 0;
        $batchSize = 100;
        $asteroidBatch = [];
        $resourceBatch = [];

        for ($i = 0; $i < $count; $i++) {
            try {
                $asteroid = $this->generateAsteroid();
                $resources = $this->generateResourcesFromPools($asteroid['value'], $asteroid['size']);
                $resourceBatch[] = $resources;
                $minStationDistance = $this->calculateMinStationDistance($asteroid['size'], $resources);

                if ($centerX !== null && $centerY !== null && $radius !== null) {
                    $coordinate = $this->generateAsteroidCoordinateInRadius($centerX, $centerY, $radius, $minStationDistance, $resources, $asteroid['size']);
                } else {
                    $coordinate = $this->generateAsteroidCoordinate($minStationDistance, $resources, $asteroid['size']);
                }
                $asteroid['x'] = $coordinate['x'];
                $asteroid['y'] = $coordinate['y'];

                $asteroidBatch[] = $asteroid;

                if (count($asteroidBatch) >= $batchSize) {
                    $createdAsteroids = $this->saveBatchedAsteroids($asteroidBatch);
                    $this->saveBatchedResources($createdAsteroids, $resourceBatch);
                    $asteroids = array_merge($asteroids, $createdAsteroids);
                    $asteroidBatch = [];
                    $resourceBatch = [];
                }
            } catch (\Exception $e) {
                $failures++;
                Log::error("Fehler bei der Generierung eines Asteroiden: " . $e->getMessage());
                if ($failures > $maxFailures) {
                    Log::warning("Zu viele Fehler bei der Asteroiden-Generierung ({$failures}). Abbruch nach {$i} von {$count} Asteroiden.");
                    break;
                }
                $i--;
            }
        }

        if (count($asteroidBatch) > 0) {
            $createdAsteroids = $this->saveBatchedAsteroids($asteroidBatch);
            $this->saveBatchedResources($createdAsteroids, $resourceBatch);
            $asteroids = array_merge($asteroids, $createdAsteroids);
        }

        return $asteroids;
    }

    private function saveBatchedAsteroids(array $asteroids): array
    {
        Asteroid::insert($asteroids);
        $names = array_column($asteroids, 'name');
        return Asteroid::whereIn('name', $names)->get()->toArray();
    }

    private function saveBatchedResources(array $asteroids, array $resourcesData): void
    {
        $resourcesForInsert = [];
        foreach ($asteroids as $index => $asteroid) {
            if (!isset($resourcesData[$index])) continue;
            foreach ($resourcesData[$index] as $resourceType => $amount) {
                $resourcesForInsert[] = [
                    'asteroid_id' => $asteroid['id'],
                    'resource_type' => $resourceType,
                    'amount' => $amount
                ];
            }
        }
        if ($resourcesForInsert) {
            AsteroidResource::insert($resourcesForInsert);
        }
    }

    private function generateAsteroid(): array
    {
        $asteroidBaseFaktor = rand($this->asteroidConfig['asteroid_faktor']['min'], $this->asteroidConfig['asteroid_faktor']['max']);
        $asteroidSize = $this->generateAsteroidSize($this->asteroidConfig['asteroid_size']);
        $asteroidFaktorMultiplier = $this->asteroidConfig['asteroid_faktor_multiplier'][$asteroidSize];
        $asteroidBaseMultiplier = rand($asteroidFaktorMultiplier['min'] * 100, $asteroidFaktorMultiplier['max'] * 100) / 100;
        $asteroidValue = floor($asteroidBaseFaktor * $asteroidBaseMultiplier);

        return [
            'name' => $this->generateAsteroidName($asteroidSize, $asteroidValue, $asteroidBaseMultiplier),
            'size' => $asteroidSize,
            'base' => $asteroidBaseFaktor,
            'multiplier' => $asteroidBaseMultiplier,
            'value' => $asteroidValue,
            'pixel_size' => $this->asteroidConfig['asteroid_img_size'][$asteroidSize] ?? 4,
        ];
    }

    private function generateAsteroidSize(array $asteroidSize): string
    {
        $totalWeight = array_sum($asteroidSize);
        $randomValue = rand(0, $totalWeight - 1);
        $cumulativeWeight = 0;
        foreach ($asteroidSize as $size => $weight) {
            $cumulativeWeight += $weight;
            if ($randomValue < $cumulativeWeight) {
                return $size;
            }
        }
        return 'small';
    }

    private function generateAsteroidName(string $size, int $value, float $multiplier): string
    {
        $prefix = substr($size, 0, 2);
        $randomString = $this->generateRandomString(2);
        $randomString2 = $this->generateRandomString(2);
        return "{$randomString}{$prefix}{$randomString2}{$value}-" . floor($multiplier);
    }

    private function generateRandomString(int $length): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    private function generateResourcesFromPools($asteroidValue, string $size): array
    {
        $poolWeights = $this->asteroidConfig['pool_weights'];
        $num_resource_range = $this->asteroidConfig['num_resource_range'];
        $resource_ratio_range = $this->asteroidConfig['resource_ratio_range'];
        $num_resources = rand($num_resource_range[0], $num_resource_range[1]);

        if ($size === 'extreme') {
            unset($poolWeights['extreme_value'], $poolWeights['high_value']);
            $total = array_sum($poolWeights);
            $poolWeights = array_map(fn($w) => $w / $total, $poolWeights);
        }

        $resource_ratios = [];
        for ($i = 0; $i < $num_resources; $i++) {
            $pool = $this->getRandomPool($poolWeights);
            $resource = $this->asteroidConfig['resource_pools'][$pool]['resources'][array_rand($this->asteroidConfig['resource_pools'][$pool]['resources'])];
            $resource_ratios[$resource] = ($resource_ratios[$resource] ?? 0) + rand($resource_ratio_range[0], $resource_ratio_range[1]);
        }

        $total_ratio = array_sum($resource_ratios);
        if ($total_ratio === 0) return [];
        return array_map(fn($r) => intval($r / $total_ratio * $asteroidValue), $resource_ratios);
    }

    private function getRandomPool($pool_weights)
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0;
        foreach ($pool_weights as $pool => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) return $pool;
        }
        return array_key_first($pool_weights);
    }

    private function calculateMinStationDistance(string $size, array $resources): int
    {
        $baseDistance = $this->asteroidConfig['size_min_distance']['base'];
        $sizeModifier = $this->asteroidConfig['size_min_distance']["{$size}_asteroid"];
        $distance = $baseDistance * $sizeModifier;
        $resourceDistance = $this->getResourceMinDistance($resources);
        return max($distance, $resourceDistance);
    }

    private function getResourceMinDistance(array $resources): int
    {
        if (empty($resources)) return 0;
        $baseDistance = $this->asteroidConfig['resource_min_distances']['base'];
        $maxModifier = 0;
        foreach ($resources as $resourceType) {
            foreach ($this->asteroidConfig['resource_pools'] as $poolName => $pool) {
                if (in_array($resourceType, $pool['resources'])) {
                    $modifier = $this->asteroidConfig['resource_min_distances'][$poolName] ?? 1.0;
                    $maxModifier = max($maxModifier, $modifier);
                    break;
                }
            }
        }
        return $baseDistance * $maxModifier;
    }

    private function generateAsteroidCoordinateInRadius(int $centerX, int $centerY, int $radius, int $minStationDistance, array $resources = [], string $size = 'small'): array
    {
        $maxAttempts = 5000;
        $attempts = 0;
        do {
            $angle = mt_rand(0, 360);
            $distance = mt_rand(0, $radius);
            $x = (int) round($centerX + cos(deg2rad($angle)) * $distance);
            $y = (int) round($centerY + sin(deg2rad($angle)) * $distance);

            $isAllowedForAsteroid = true;
            $resourceLevel = $this->determineResourceLevel($resources);

            if ($this->isInReservedStationRegion($x, $y, $resourceLevel)) {
                $isAllowedForAsteroid = false;
            }
            if ($isAllowedForAsteroid) {
                $isAllowedForAsteroid = !$this->isCollidingWithStation($x, $y, $minStationDistance) &&
                    !$this->isCollidingWithAsteroidDB($x, $y, $this->config['asteroid_distance']);
            }
            if ($isAllowedForAsteroid && $size === 'extreme') {
              $extremeDistance = $this->config['extreme_asteroid_distance'] ?? 5000;
              $isAllowedForAsteroid = !$this->isCollidingWithExtremeAsteroid($x, $y, $extremeDistance);
            }
            $attempts++;
        } while (!$isAllowedForAsteroid && $attempts < $maxAttempts);

        if (!$isAllowedForAsteroid) {
            return $this->generateAsteroidCoordinate($minStationDistance, $resources);
        }
        return ['x' => $x, 'y' => $y];
    }

    private function generateAsteroidCoordinate(int $minStationDistance, array $resources = [], string $size = 'small'): array
    {
        $asteroidToAsteroidDistance = $this->config['asteroid_distance'];
        $minDistance = $this->config['asteroid_to_station_distance'];
        $spawnArea = [
            'min_x' => 0,
            'min_y' => 0,
            'max_x' => $this->config['size'],
            'max_y' => $this->config['size'],
        ];
        $maxAttempts = 5000;
        $attempts = 0;
        $resourceLevel = $this->determineResourceLevel($resources);

        // Regionen-Logik
        $regionSize = max($minDistance * 2, $minStationDistance);
        $regionsX = ceil(($spawnArea['max_x'] - $spawnArea['min_x']) / $regionSize);
        $regionsY = ceil(($spawnArea['max_y'] - $spawnArea['min_y']) / $regionSize);
        $triedRegions = [];

        do {
            if ($attempts > 1000) {
                $rx = rand(0, $regionsX - 1);
                $ry = rand(0, $regionsY - 1);
                $regionKey = "{$rx}:{$ry}";

                if (isset($triedRegions[$regionKey]) && $triedRegions[$regionKey] > 10) {
                    $attempts++;
                    continue;
                }

                $triedRegions[$regionKey] = ($triedRegions[$regionKey] ?? 0) + 1;
                $x = $spawnArea['min_x'] + ($rx * $regionSize) + rand(0, $regionSize);
                $y = $spawnArea['min_y'] + ($ry * $regionSize) + rand(0, $regionSize);
            } else {
                $x = rand($spawnArea['min_x'], $spawnArea['max_x']);
                $y = rand($spawnArea['min_y'], $spawnArea['max_y']);
            }

            $isAllowedForAsteroid = true;
            if ($this->isInReservedStationRegion($x, $y, $resourceLevel)) {
                $isAllowedForAsteroid = false;
            }
            if ($isAllowedForAsteroid) {
                $isAllowedForAsteroid = !$this->isCollidingWithStation($x, $y, $minStationDistance) &&
                    !$this->isCollidingWithAsteroidDB($x, $y, $asteroidToAsteroidDistance);
            }
            if ($isAllowedForAsteroid && $size === 'extreme') {
              $extremeDistance = $this->config['extreme_asteroid_distance'] ?? 5000;
              $isAllowedForAsteroid = !$this->isCollidingWithExtremeAsteroid($x, $y, $extremeDistance);
            }

            $attempts++;
            if ($attempts % 1000 === 0 && $minDistance > 100) {
                $minDistance = max(100, $minDistance * 0.8);
            }
        } while (!$isAllowedForAsteroid && $attempts < $maxAttempts);

        if (!$isAllowedForAsteroid) {
            throw new \Exception("Konnte keine gültige Position finden nach {$attempts} Versuchen.");
        }

        return ['x' => (int) $x, 'y' => (int) $y];
    }

    private function isCollidingWithAsteroidDB(int $x, int $y, int $minDistance): bool
    {
        return Asteroid::whereBetween('x', [$x - $minDistance, $x + $minDistance])
            ->whereBetween('y', [$y - $minDistance, $y + $minDistance])
            ->get()
            ->contains(function ($asteroid) use ($x, $y, $minDistance) {
                $distance = sqrt(pow($asteroid->x - $x, 2) + pow($asteroid->y - $y, 2));
                return $distance < $minDistance;
            });
    }

    private function isCollidingWithExtremeAsteroid(int $x, int $y, int $minDistance): bool
    {
        return Asteroid::where('size', 'extreme')
            ->whereBetween('x', [$x - $minDistance, $x + $minDistance])
            ->whereBetween('y', [$y - $minDistance, $y + $minDistance])
            ->get()
            ->contains(function ($asteroid) use ($x, $y, $minDistance) {
                $distance = sqrt(pow($asteroid->x - $x, 2) + pow($asteroid->y - $y, 2));
                return $distance < $minDistance;
            });
    }

    public function isCollidingWithStation(int $x, int $y, int $minDistanceFromStation): bool
    {
        foreach ($this->stations as $station) {
            $distance = sqrt(pow($station['x'] - $x, 2) + pow($station['y'] - $y, 2));
            if ($distance < $minDistanceFromStation) {
                return true;
            }
        }

        $reservedRegions = $this->stationService->getReservedStationRegions();
        foreach ($reservedRegions as $region) {
            $stationX = $region['station_x'] ?? $region['x'] ?? 0;
            $stationY = $region['station_y'] ?? $region['y'] ?? 0;
            $distance = sqrt(pow($stationX - $x, 2) + pow($stationY - $y, 2));
            if ($distance < $minDistanceFromStation) {
                return true;
            }
        }
        return false;
    }

    private function isInReservedStationRegion(int $x, int $y, string $resourceLevel = 'any'): bool
    {
        $reservedRegions = $this->stationService->getReservedStationRegions();

        foreach ($reservedRegions as $region) {
            $stationX = $region['station_x'] ?? $region['x'] ?? 0;
            $stationY = $region['station_y'] ?? $region['y'] ?? 0;
            $distance = sqrt(pow($stationX - $x, 2) + pow($stationY - $y, 2));
            $innerRadius = $region['inner_radius'] ?? $this->config['station_inner_radius'] ?? 450;
            if ($distance <= $innerRadius) {
                return true;
            }
            $outerRadius = $region['outer_radius'] ?? $this->config['station_outer_radius'] ?? 4000;
            if ($distance <= $outerRadius && $resourceLevel !== 'low') {
                return true;
            }
        }
        return false;
    }

    private function determineResourceLevel(array $resources): string
    {
        if (empty($resources)) return 'low';
        $valueHints = [];
        foreach ($this->asteroidConfig['resource_pools'] as $poolName => $pool) {
            if (strpos($poolName, 'high_value') !== false || strpos($poolName, 'extreme') !== false) {
                $valueHints[$poolName] = 'high';
            } else if (strpos($poolName, 'medium_value') !== false) {
                $valueHints[$poolName] = 'medium';
            } else {
                $valueHints[$poolName] = 'low';
            }
        }
        $highestLevel = 'low';
        $levelRanking = ['low' => 1, 'medium' => 2, 'high' => 3];
        foreach ($resources as $resourceType) {
            foreach ($this->asteroidConfig['resource_pools'] as $poolName => $pool) {
                if (in_array($resourceType, $pool['resources'])) {
                    $level = $valueHints[$poolName];
                    if ($levelRanking[$level] > $levelRanking[$highestLevel]) {
                        $highestLevel = $level;
                    }
                    break;
                }
            }
        }
        return $highestLevel;
    }
}
