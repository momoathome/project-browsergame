<?php

namespace Orion\Modules\Asteroid\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Asteroid\Models\Asteroid;

class UniverseService
{
    protected $config;
    protected $gridSize = 2000;
    protected $spatialIndex = [];

    public function __construct()
    {
        $this->config = config('game.core');
        $this->buildSpatialIndex();
    }

    /**
     * Reserviert Regionen für Stationen im Universum
     *
     * @param int $numStations Anzahl der zu reservierenden Regionen
     * @param bool $forceRefresh Cache ignorieren und neu generieren
     * @return array Liste der reservierten Regionen
     */
    public function reserveStationRegions(int $numStations = 50, bool $forceRefresh = false): array
    {
        $cacheKey = 'universe:reserved-station-regions';

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Grundkonfiguration
        $reservedRegions = [];
        $universeSize = $this->config['size'];
        $stationDistance = $this->config['station_distance'];
        $borderDistance = $this->config['border_distance'];

        // Radius-Definitionen für Stationsregionen
        $innerRadius = $this->config['station_inner_radius'];
        $outerRadius = $this->config['station_outer_radius'];

        Log::info("Reserviere {$numStations} Stationsstandorte im Universum (Größe: {$universeSize}x{$universeSize})");

        // Versuchslimit 
        $attempts = 0;
        $maxAttempts = $numStations * 20;

        while (count($reservedRegions) < $numStations && $attempts < $maxAttempts) {
            // Zufällige Position im Universum wählen
            $stationX = rand($borderDistance, $universeSize - $borderDistance);
            $stationY = rand($borderDistance, $universeSize - $borderDistance);

            // Prüfen, ob genügend Abstand zu anderen Stationen eingehalten wird
            if (!$this->isTooCloseToOtherStations($stationX, $stationY, $reservedRegions, $stationDistance)) {
                // Station hinzufügen
                $regionId = count($reservedRegions) + 1;
                $reservedRegions[] = [
                    'id' => $regionId,
                    'station_x' => (int) $stationX,
                    'station_y' => (int) $stationY,
                    'inner_radius' => $innerRadius,
                    'outer_radius' => $outerRadius,
                ];

                if ($regionId <= 5) {
                    Log::debug("Station {$regionId} platziert: X={$stationX}, Y={$stationY}");
                }
            }

            $attempts++;
        }

        // Abschließendes Logging
        $createdCount = count($reservedRegions);
        $percentage = ($createdCount / $numStations) * 100;
        Log::info("Reserviert: {$createdCount} von {$numStations} Stationsregionen ({$percentage}%) nach {$attempts} Versuchen");

        // Im Cache speichern
        Cache::put($cacheKey, $reservedRegions, 604800); // 1 Woche

        return $reservedRegions;
    }

    /**
     * Findet eine freie Stationsregion und markiert sie als verwendet
     */
    public function assignStationRegion(): array
    {
        $reservedRegions = $this->getReservedStationRegions();
        $usedRegions = Cache::get('universe:used-station-regions', []);

        if (empty($reservedRegions)) {
            throw new \Exception("Keine reservierten Stationsregionen gefunden.");
        }

        // Verfügbare Regionen finden
        $availableRegions = [];
        foreach ($reservedRegions as $index => $region) {
            $regionId = $region['id'] ?? $index;
            if (!in_array($regionId, $usedRegions)) {
                $availableRegions[] = ['index' => $index, 'region' => $region];
            }
        }

        if (empty($availableRegions)) {
            throw new \Exception("Alle reservierten Regionen sind bereits belegt.");
        }

        // Eine zufällige Region auswählen
        $selectedRegion = $availableRegions[array_rand($availableRegions)];
        $region = $selectedRegion['region'];
        $index = $selectedRegion['index'];

        // Die vordefinierten Koordinaten verwenden
        $x = (int) ($region['station_x'] ?? $region['x'] ?? 0);
        $y = (int) ($region['station_y'] ?? $region['y'] ?? 0);

        // Region als verwendet markieren
        $regionId = $reservedRegions[$index]['id'] ?? $index;
        $usedRegions[] = $regionId;
        Cache::put('universe:used-station-regions', $usedRegions, 604800);
        Cache::forget('universe:stations');

        return ['x' => $x, 'y' => $y];
    }

    /**
     * Gibt reservierte Stationsregionen zurück oder erstellt sie bei Bedarf
     */
    public function getReservedStationRegions(bool $forceRefresh = false): array
    {
        return $this->reserveStationRegions(50, $forceRefresh);
    }

    /**
     * Validiert, dass alle reservierten Regionen frei von Kollisionen sind
     */
    public function validateReservedRegions(array $regions): array
    {
        $validRegions = [];
        $minDistance = config('game.core.station_distance');

        foreach ($regions as $key => $region) {
            $x = $region['station_x'] ?? $region['x'] ?? 0;
            $y = $region['station_y'] ?? $region['y'] ?? 0;

            // Prüfen ob diese Region mit anderen Regionen kollidiert
            $isValid = true;
            foreach ($regions as $otherKey => $otherRegion) {
                if ($key === $otherKey)
                    continue;

                $otherX = $otherRegion['station_x'] ?? $otherRegion['x'] ?? 0;
                $otherY = $otherRegion['station_y'] ?? $otherRegion['y'] ?? 0;

                $distance = sqrt(pow($x - $otherX, 2) + pow($y - $otherY, 2));
                if ($distance < $minDistance) {
                    $isValid = false;
                    break;
                }
            }

            if ($isValid) {
                $validRegions[] = $region;
            }
        }

        return $validRegions;
    }

    /**
     * Prüft ob der Abstand zu anderen Stationen ausreichend ist
     */
    private function isTooCloseToOtherStations(float $x, float $y, array $existingRegions, float $minDistance): bool
    {
        foreach ($existingRegions as $region) {
            $distance = sqrt(pow($region['station_x'] - $x, 2) + pow($region['station_y'] - $y, 2));
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
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);
        $checkRadius = ceil($minDistance / $this->gridSize) + 1;

        for ($i = $gridX - $checkRadius; $i <= $gridX + $checkRadius; $i++) {
            for ($j = $gridY - $checkRadius; $j <= $gridY + $checkRadius; $j++) {
                $key = "{$i}:{$j}";

                if (!isset($this->spatialIndex[$key])) {
                    continue;
                }

                foreach ($this->spatialIndex[$key] as $object) {
                    if ($object['type'] !== 'asteroid')
                        continue;

                    $distance = sqrt(pow($object['x'] - $x, 2) + pow($object['y'] - $y, 2));
                    if ($distance < $minDistance) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Prüft ob die Position zu nahe an wertvollen Ressourcen ist
     */
    public function isNearValuableResources(int $x, int $y, int $maxDistance): bool
    {
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);
        $checkRadius = ceil($maxDistance / $this->gridSize) + 1;

        for ($i = $gridX - $checkRadius; $i <= $gridX + $checkRadius; $i++) {
            for ($j = $gridY - $checkRadius; $j <= $gridY + $checkRadius; $j++) {
                $key = "{$i}:{$j}";

                if (!isset($this->spatialIndex[$key])) {
                    continue;
                }

                foreach ($this->spatialIndex[$key] as $object) {
                    if ($object['type'] !== 'resource')
                        continue;

                    $distance = sqrt(pow($object['x'] - $x, 2) + pow($object['y'] - $y, 2));
                    $minRequiredDistance = $this->getMinDistanceForResource($object['resource_type']);

                    if ($distance < $minRequiredDistance) {
                        return true;
                    }
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
        $resourcePools = $this->config['resource_pools'];
        $distances = $this->config['resource_min_distances'];

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

    /**
     * Lädt alle Stationen
     */
    public function getStations($forceRefresh = false)
    {
        $cacheKey = 'universe:stations';

        // Cache löschen wenn gewünscht
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 1800, function () {
            $stations = Station::all(['id', 'name', 'x', 'y'])->toArray();
            Log::debug("Stations-Cache neu geladen: " . count($stations) . " Stationen gefunden");
            return $stations;
        });
    }

    /**
     * Baut einen räumlichen Index für schnelle Abstandsprüfungen
     */
    protected function buildSpatialIndex()
    {
        // Asteroiden laden
        $asteroids = Cache::remember('universe:asteroid-index', 1800, function () {
            return Asteroid::select('id', 'x', 'y')->get();
        });

        // Ressourcen laden
        $resources = Cache::remember('universe:resource-index', 1800, function () {
            return DB::table('asteroids')
                ->join('asteroid_resources', 'asteroids.id', '=', 'asteroid_resources.asteroid_id')
                ->select('asteroids.id', 'asteroids.x', 'asteroids.y', 'asteroid_resources.resource_type')
                ->get();
        });

        // Index aufbauen
        foreach ($asteroids as $asteroid) {
            $this->addToSpatialIndex($asteroid->x, $asteroid->y, $asteroid->id, 'asteroid');
        }

        foreach ($resources as $resource) {
            $this->addToSpatialIndex($resource->x, $resource->y, $resource->id, 'resource', $resource->resource_type);
        }
    }

    /**
     * Fügt ein Objekt zum räumlichen Index hinzu
     */
    protected function addToSpatialIndex($x, $y, $id, $type, $resourceType = null)
    {
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);
        $key = "{$gridX}:{$gridY}";

        if (!isset($this->spatialIndex[$key])) {
            $this->spatialIndex[$key] = [];
        }

        $entry = [
            'id' => $id,
            'x' => $x,
            'y' => $y,
            'type' => $type
        ];

        if ($resourceType) {
            $entry['resource_type'] = $resourceType;
        }

        $this->spatialIndex[$key][] = $entry;
    }

    /**
     * Löscht alle zwischengespeicherten Daten zum Universum
     */
    public static function clearCache()
    {
        Cache::forget('universe:stations');
        Cache::forget('universe:asteroid-index');
        Cache::forget('universe:resource-index');
        Cache::forget('universe:reserved-station-regions');
    }
}
