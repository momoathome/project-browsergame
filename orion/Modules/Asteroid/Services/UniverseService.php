<?php

namespace Orion\Modules\Asteroid\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;
use Orion\Modules\Station\Models\StationRegion;

class UniverseService
{
    protected $config;
    protected $asteroidConfig;
    protected $asteroidGenerator;

    public function __construct(AsteroidGenerator $asteroidGenerator)
    {
        $this->config = config('game.core');
        $this->asteroidConfig = config('game.asteroids');
        $this->asteroidGenerator = $asteroidGenerator;
    }

    public function reserveStationRegions(int $numStations = 25): void
    {
        $universeSize = $this->config['size'];
        $stationDistance = $this->config['station_distance'];
        $borderDistance = $this->config['border_distance'];
        $innerRadius = $this->config['station_inner_radius'];
        $outerRadius = $this->config['station_outer_radius'];

        Log::info("Reserviere {$numStations} Stationsstandorte im Universum (Größe: {$universeSize}x{$universeSize})");

        $created = 0;
        $attempts = 0;
        $maxAttempts = $numStations * 20;

        // Lade existierende Regionen aus der DB
        $existingRegions = StationRegion::all(['x', 'y',])->toArray();

        while ($created < $numStations && $attempts < $maxAttempts) {
            $stationX = rand(0, $universeSize - $borderDistance);
            $stationY = rand(0, $universeSize - $borderDistance);

            // Prüfe Abstand zu anderen StationRegions
            if ($this->isTooCloseToOtherStationRegions($stationX, $stationY, $existingRegions, $stationDistance)) {
                $attempts++;
                continue;
            }

            // Prüfe Abstand zu Asteroiden
            if ($this->isCollidingWithAsteroid($stationX, $stationY, $innerRadius)) {
                $attempts++;
                continue;
            }

            // Prüfe Abstand zu wertvollen Ressourcen
            if ($this->isNearValuableResources($stationX, $stationY, $outerRadius)) {
                $attempts++;
                continue;
            }

            // prüfe Abstand zu anderen Stationen
            if ($this->isTooCloseToExistingStations($stationX, $stationY, $stationDistance)) {
                $attempts++;
                continue;
            }

            // Region ist gültig, in DB speichern
            StationRegion::create([
                'x' => $stationX,
                'y' => $stationY,
                'used' => false,
            ]);
            $existingRegions[] = [
                'x' => $stationX,
                'y' => $stationY,
            ];
            $created++;
            $attempts++;
        }

        $percentage = ($created / $numStations) * 100;
        Log::info("Reserviert: {$created} von {$numStations} Stationsregionen ({$percentage}%) nach {$attempts} Versuchen");
    }

    /**
     * Findet eine freie Stationsregion und markiert sie als verwendet
     */
    public function assignStationRegion($userId)
    {
        $region = StationRegion::where('used', false)->first();
       
        if (!$region) {
            $player_count = \App\Models\User::count();
            // Asteroidenzahl und Universumsgröße erhöhen
            $this->asteroidGenerator->generateAsteroids($player_count * 25);

            // Neue StationRegions generieren
            $this->reserveStationRegions($this->config['default_stations']);

            // Nochmals versuchen, eine freie Region zu bekommen
            $region = StationRegion::where('used', false)->first();
            if (!$region) {
                throw new \Exception("Auch nach Erweiterung keine freie Stationsregion verfügbar!");
            }
        }

        $region->used = true;
        $region->assigned_to_user_id = $userId;
        $region->save();
        return ['x' => $region->x, 'y' => $region->y];
    }

    /**
     * Prüft ob der Abstand zu anderen Station Regionen ausreichend ist
     */
    private function isTooCloseToOtherStationRegions(float $x, float $y, array $existingRegions = [], float $minDistance): bool
    {
        foreach ($existingRegions as $region) {
            $distance = sqrt(pow($region['x'] - $x, 2) + pow($region['y'] - $y, 2));
            if ($distance < $minDistance) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prüft ob die Position zu nahe an Asteroiden ist
     */
    public function isCollidingWithAsteroid(int $x, int $y, int $minDistance): bool
    {
        return Asteroid::whereBetween('x', [$x - $minDistance, $x + $minDistance])
            ->whereBetween('y', [$y - $minDistance, $y + $minDistance])
            ->get()
            ->contains(function ($asteroid) use ($x, $y, $minDistance) {
                $distance = sqrt(pow($asteroid->x - $x, 2) + pow($asteroid->y - $y, 2));
                return $distance < $minDistance;
            });
    }

    /**
     * Prüft ob die Position zu nahe an wertvollen Ressourcen ist
     */
    public function isNearValuableResources(int $x, int $y, int $maxDistance): bool
    {
        $asteroids = Asteroid::whereBetween('x', [$x - $maxDistance, $x + $maxDistance])
            ->whereBetween('y', [$y - $maxDistance, $y + $maxDistance])
            ->get();
    
        foreach ($asteroids as $asteroid) {
            foreach ($asteroid->resources as $resource) {
                $distance = sqrt(pow($asteroid->x - $x, 2) + pow($asteroid->y - $y, 2));
                $minRequiredDistance = $this->getMinDistanceForResource($resource->resource_type);
    
                if ($distance < $minRequiredDistance) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Bestimmt den Mindestabstand für einen Ressourcentyp
     */
    private function getMinDistanceForResource(string $resourceType): int
    {
        $resourcePools = $this->asteroidConfig['resource_pools'];
        $distances = $this->asteroidConfig['resource_min_distances'];

        static $resourcePoolMapping = null;

        if ($resourcePoolMapping === null) {
            $resourcePoolMapping = [];
            foreach ($resourcePools as $poolName => $pool) {
                foreach ($pool['resources'] as $resource) {
                    $resourcePoolMapping[$resource] = $poolName;
                }
            }
        }

        if (isset($resourcePoolMapping[$resourceType])) {
            return $distances[$resourcePoolMapping[$resourceType]];
        }

        return 0; // Fallback
    }

    private function isTooCloseToExistingStations(float $x, float $y, float $minDistance): bool
    {
        $stations = Station::all(['x', 'y']);
        foreach ($stations as $station) {
            $distance = sqrt(pow($station->x - $x, 2) + pow($station->y - $y, 2));
            if ($distance < $minDistance) {
                return true;
            }
        }
        return false;
    }

}
