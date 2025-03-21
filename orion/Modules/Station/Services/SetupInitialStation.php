<?php

namespace Orion\Modules\Station\Services;

use Orion\Modules\Station\Models\Station;
use Orion\Modules\Asteroid\Models\Asteroid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SetupInitialStation
{
    private $config;
    private $stations;
    private $asteroidSpatialIndex = [];
    private $resourceSpatialIndex = [];
    private $gridSize = 2000;
    private $resourceConfig;

    public function __construct()
    {
        $this->config = config('game.stations');
        $this->resourceConfig = config('game.asteroids');
        $this->stations = $this->getStations();
        $this->buildAsteroidSpatialIndex();
        $this->buildResourceSpatialIndex();
    }

    public function create(int $userId, string $userName)
    {
        // Versuche zuerst, einen vorberechneten Standort zu verwenden
        try {
            $coordinate = $this->getPreCalculatedStationCoordinate();
        } catch (\Exception $e) {
            // Fallback zur normalen Koordinatengenerierung
            $coordinate = $this->generateStationCoordinate();
        }

        $station = Station::create([
            'user_id' => $userId,
            'name' => $userName,
            'x' => $coordinate['x'],
            'y' => $coordinate['y'],
        ]);

        Cache::forget('setup-station:stations');

        return $station;
    }

    public function generateStationCoordinate(): array
    {
        $minDistance = $this->config['min_distance'];
        $asteroidMinDistance = $this->config['asteroid_min_distance'];
        $universeBorderDistance = $this->config['universe_border_distance'];
        $universeSize = $this->config['universe_size'];
        $maxAttempts = 5000;
        $attempts = 0;

        // Regionen-basierter Ansatz für intelligentere Suche
        $regionSize = $minDistance;
        $regionsX = ceil(($universeSize - 2 * $universeBorderDistance) / $regionSize);
        $regionsY = ceil(($universeSize - 2 * $universeBorderDistance) / $regionSize);
        $triedRegions = [];

        // Der maximale Abstand, den wir prüfen müssen (für extreme_value Ressourcen)
        $maxResourceDistance = $this->resourceConfig['resource_min_distances']['extreme_value'];

        do {
            // Intelligenterer Ansatz nach vielen Versuchen
            if ($attempts > 500) {
                $rx = rand(0, $regionsX - 1);
                $ry = rand(0, $regionsY - 1);
                $regionKey = "{$rx}:{$ry}";

                // Region überspringen, wenn sie bereits oft versucht wurde
                if (isset($triedRegions[$regionKey]) && $triedRegions[$regionKey] > 5) {
                    $attempts++;
                    continue;
                }

                $triedRegions[$regionKey] = ($triedRegions[$regionKey] ?? 0) + 1;

                // Position innerhalb der Region wählen
                $x = $universeBorderDistance + ($rx * $regionSize) + rand(0, $regionSize);
                $y = $universeBorderDistance + ($ry * $regionSize) + rand(0, $regionSize);
            } else {
                // Einfacher zufälliger Ansatz für die ersten 500 Versuche
                $x = rand($universeBorderDistance, $universeSize - $universeBorderDistance);
                $y = rand($universeBorderDistance, $universeSize - $universeBorderDistance);
            }

            $isValid = !$this->isCollidingWithOtherStation($x, $y, $minDistance) &&
                !$this->isCollidingWithAsteroid($x, $y, $asteroidMinDistance) &&
                !$this->isNearValuableResources($x, $y, $maxResourceDistance);

            $attempts++;
        } while (!$isValid && $attempts < $maxAttempts);

        if (!$isValid) {
            throw new \Exception("Konnte keine gültige Position für die Station finden nach {$attempts} Versuchen.");
        }

        return ['x' => $x, 'y' => $y];
    }

    private function isCollidingWithOtherStation(int $x, int $y, int $minDistance): bool
    {
        foreach ($this->stations as $station) {
            $distance = sqrt(pow($station['x'] - $x, 2) + pow($station['y'] - $y, 2));
            if ($distance < $minDistance) {
                return true;
            }
        }

        return false;
    }

    private function isCollidingWithAsteroid(int $x, int $y, int $minDistance): bool
    {
        // Räumlichen Index nutzen für schnelle Kollisionsprüfung
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);
        $checkRadius = ceil($minDistance / $this->gridSize) + 1;

        for ($i = $gridX - $checkRadius; $i <= $gridX + $checkRadius; $i++) {
            for ($j = $gridY - $checkRadius; $j <= $gridY + $checkRadius; $j++) {
                $key = "{$i}:{$j}";

                if (!isset($this->asteroidSpatialIndex[$key])) {
                    continue;
                }

                foreach ($this->asteroidSpatialIndex[$key] as $asteroid) {
                    $distance = sqrt(pow($asteroid['x'] - $x, 2) + pow($asteroid['y'] - $y, 2));
                    if ($distance < $minDistance) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function isNearValuableResources(int $x, int $y, int $maxDistance): bool
    {
        // Räumlichen Index für Ressourcen nutzen
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);
        $checkRadius = ceil($maxDistance / $this->gridSize) + 1;

        for ($i = $gridX - $checkRadius; $i <= $gridX + $checkRadius; $i++) {
            for ($j = $gridY - $checkRadius; $j <= $gridY + $checkRadius; $j++) {
                $key = "{$i}:{$j}";

                if (!isset($this->resourceSpatialIndex[$key])) {
                    continue;
                }

                foreach ($this->resourceSpatialIndex[$key] as $resource) {
                    $distance = sqrt(pow($resource['x'] - $x, 2) + pow($resource['y'] - $y, 2));
                    $minRequiredDistance = $this->getMinDistanceForResource($resource['resource_type']);

                    if ($distance < $minRequiredDistance) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getMinDistanceForResource(string $resourceType): int
    {
        $resourcePools = $this->resourceConfig['resource_pools'];
        $distances = $this->resourceConfig['resource_min_distances'];

        static $resourcePoolMapping = null;

        // Erstelle ein Cache-Mapping für schnellere Lookups
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

    protected function getStations()
    {
        return Cache::remember('setup-station:stations', 1800, function () {
            return Station::all()->map(function ($station) {
                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'x' => $station->x,
                    'y' => $station->y,
                ];
            })->toArray();
        });
    }

    protected function buildAsteroidSpatialIndex()
    {
        // Nutze Caching für den räumlichen Index der Asteroiden
        $this->asteroidSpatialIndex = Cache::remember('setup-station:asteroid-spatial-index', 1800, function () {
            $spatialIndex = [];
            $asteroids = Asteroid::select('id', 'x', 'y')->get();

            foreach ($asteroids as $asteroid) {
                $gridX = floor($asteroid->x / $this->gridSize);
                $gridY = floor($asteroid->y / $this->gridSize);
                $key = "{$gridX}:{$gridY}";

                if (!isset($spatialIndex[$key])) {
                    $spatialIndex[$key] = [];
                }

                $spatialIndex[$key][] = [
                    'id' => $asteroid->id,
                    'x' => $asteroid->x,
                    'y' => $asteroid->y
                ];
            }

            return $spatialIndex;
        });
    }

    protected function buildResourceSpatialIndex()
    {
        // Nutze Caching für den räumlichen Index der Ressourcen
        $this->resourceSpatialIndex = Cache::remember('setup-station:resource-spatial-index', 1800, function () {
            $spatialIndex = [];

            // Eine einzelne optimierte Datenbankabfrage, um alle Asteroiden mit ihren Ressourcen zu laden
            $resourceData = DB::table('asteroids')
                ->join('asteroid_resources', 'asteroids.id', '=', 'asteroid_resources.asteroid_id')
                ->select('asteroids.id', 'asteroids.x', 'asteroids.y', 'asteroid_resources.resource_type')
                ->get();

            foreach ($resourceData as $resource) {
                $gridX = floor($resource->x / $this->gridSize);
                $gridY = floor($resource->y / $this->gridSize);
                $key = "{$gridX}:{$gridY}";

                if (!isset($spatialIndex[$key])) {
                    $spatialIndex[$key] = [];
                }

                $spatialIndex[$key][] = [
                    'asteroid_id' => $resource->id,
                    'x' => $resource->x,
                    'y' => $resource->y,
                    'resource_type' => $resource->resource_type
                ];
            }

            return $spatialIndex;
        });
    }

    /**
     * Findet und speichert potenzielle Stationsstandorte für später
     *
     * @param int $count Anzahl der zu findenden potenziellen Standorte
     * @param bool $forceRefresh Cache aktualisieren auch wenn Einträge vorhanden sind
     * @param callable|null $progressCallback Optional: Callback für Fortschrittsanzeige
     * @return array Liste mit Koordinaten für potenzielle Stationen
     */
    /**
     * Findet und speichert potenzielle Stationsstandorte für später
     *
     * @param int $count Anzahl der zu findenden potenziellen Standorte
     * @param bool $forceRefresh Cache aktualisieren auch wenn Einträge vorhanden sind
     * @param callable|null $progressCallback Optional: Callback für Fortschrittsanzeige
     * @return array Liste mit Koordinaten für potenzielle Stationen
     */
    public function checkPotentialStationSpawns(int $count = 100, bool $forceRefresh = false, callable $progressCallback = null): array
    {
        $cacheKey = 'setup-station:potential-spawns';

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cachedSpawns = Cache::get($cacheKey);
            if (count($cachedSpawns) >= $count) {
                return $cachedSpawns;
            }
        }

        // Konfiguration laden
        $minDistance = $this->config['min_distance'];
        $asteroidMinDistance = $this->config['asteroid_min_distance'];
        $universeBorderDistance = $this->config['universe_border_distance'];
        $universeSize = $this->config['universe_size'];
        $maxResourceDistance = $this->resourceConfig['resource_min_distances']['extreme_value'];

        // Vorhandene Spawns wiederverwenden, falls vorhanden
        $potentialSpawns = Cache::has($cacheKey) ? Cache::get($cacheKey) : [];
        $startCount = count($potentialSpawns);

        // Erhöhung der maximalen Versuche
        $maxAttempts = $count * 1000; // 1000 Versuche pro erwarteten Fund statt 100
        $attempts = 0;
        $found = $startCount;

        // Größere Regionen für bessere Verteilung
        $regionSize = 10000; // Doppelte Größe für mehr potenzielle Standorte pro Region
        $regionsX = ceil(($universeSize - 2 * $universeBorderDistance) / $regionSize);
        $regionsY = ceil(($universeSize - 2 * $universeBorderDistance) / $regionSize);
        $regionsChecked = [];

        // Ausgabe für Debugging
        if (is_callable($progressCallback)) {
            $progressCallback(0, $count, [
                'message' => "Starte Suche mit Universum {$universeSize}x{$universeSize}, {$regionsX}x{$regionsY} Regionen"
            ]);
        }

        // Systematische Suche in allen Regionen
        while ($found < $count && $attempts < $maxAttempts) {
            // Vollständig zufällige Positionen mit höherer Wahrscheinlichkeit testen
            if (rand(0, 3) > 0 || $attempts < 1000) {
                // Vollständig zufällige Position im Universum
                $x = rand($universeBorderDistance, $universeSize - $universeBorderDistance);
                $y = rand($universeBorderDistance, $universeSize - $universeBorderDistance);
            } else {
                // Regions-basierte Suche als Fallback
                $rx = rand(0, $regionsX - 1);
                $ry = rand(0, $regionsY - 1);
                $regionKey = "{$rx}:{$ry}";

                // Regionen nicht zu oft wiederholen
                if (isset($regionsChecked[$regionKey]) && $regionsChecked[$regionKey] > 10) {
                    $attempts++;
                    continue;
                }

                $regionsChecked[$regionKey] = ($regionsChecked[$regionKey] ?? 0) + 1;

                // Position innerhalb der Region wählen
                $x = $universeBorderDistance + ($rx * $regionSize) + rand(0, $regionSize);
                $y = $universeBorderDistance + ($ry * $regionSize) + rand(0, $regionSize);
            }

            // Validierung in einzelne Schritte aufteilen für besseres Debugging
            $isValidStation = !$this->isCollidingWithOtherStation($x, $y, $minDistance);
            $isValidAsteroid = !$this->isCollidingWithAsteroid($x, $y, $asteroidMinDistance);
            $isValidResource = !$this->isNearValuableResources($x, $y, $maxResourceDistance);

            $isValid = $isValidStation && $isValidAsteroid && $isValidResource;

            // Zusätzliches Debugging alle 1000 Versuche
            if ($attempts % 1000 === 0 && is_callable($progressCallback)) {
                $progressCallback($found - $startCount, $count, [
                    'attempts' => $attempts,
                    'currentPosition' => ['x' => $x, 'y' => $y],
                    'validationResults' => [
                        'station' => $isValidStation,
                        'asteroid' => $isValidAsteroid,
                        'resource' => $isValidResource
                    ]
                ]);
            }

            if ($isValid) {
                $potentialSpawns[] = ['x' => $x, 'y' => $y];
                $found++;

                // Callback für Fortschrittsanzeige
                if (is_callable($progressCallback)) {
                    $progressCallback($found - $startCount, $count);
                }

                // Zwischenspeicherung alle 10 gefundenen Standorte
                if ($found % 10 === 0) {
                    Cache::put($cacheKey, $potentialSpawns, 43200);
                }
            }

            $attempts++;
        }

        // Finales Ergebnis im Cache speichern
        Cache::put($cacheKey, $potentialSpawns, 43200);

        // Debugging-Zusammenfassung
        if (is_callable($progressCallback)) {
            $progressCallback($found - $startCount, $count, [
                'totalAttempts' => $attempts,
                'successRate' => ($found - $startCount) > 0 ? $attempts / ($found - $startCount) : 0,
                'message' => "Suche abgeschlossen mit {$found} gefundenen Standorten nach {$attempts} Versuchen."
            ]);
        }

        return $potentialSpawns;
    }

    /**
     * Ruft einen vorab berechneten Stationsstandort ab oder generiert einen neuen
     *
     * @return array Koordinaten für eine neue Station
     * @throws \Exception Wenn keine gültige Position gefunden werden kann
     */
    public function getPreCalculatedStationCoordinate(): array
    {
        $cacheKey = 'setup-station:potential-spawns';

        // Wenn wir vorberechnete Standorte haben, nehmen wir einen davon
        if (Cache::has($cacheKey)) {
            $potentialSpawns = Cache::get($cacheKey);

            if (count($potentialSpawns) > 0) {
                // Zufälligen Standort auswählen
                $index = rand(0, count($potentialSpawns) - 1);
                $spawn = $potentialSpawns[$index];

                // Gewählten Standort aus dem Cache entfernen
                unset($potentialSpawns[$index]);
                $potentialSpawns = array_values($potentialSpawns); // Array neu indizieren

                // Cache aktualisieren
                Cache::put($cacheKey, $potentialSpawns, 43200);

                return $spawn;
            }
        }

        // Wenn keine vorberechneten Standorte verfügbar sind, erstellen wir neue
        $spawns = $this->checkPotentialStationSpawns(10);

        if (count($spawns) > 0) {
            // Ersten Standort verwenden und aus dem Cache entfernen
            $spawn = array_shift($spawns);
            Cache::put($cacheKey, $spawns, 43200);
            return $spawn;
        }

        // Wenn immer noch kein Standort gefunden wurde, zur regulären Methode zurückkehren
        return $this->generateStationCoordinate();
    }

    public static function clearCache()
    {
        Cache::forget('setup-station:stations');
        Cache::forget('setup-station:asteroid-spatial-index');
        Cache::forget('setup-station:resource-spatial-index');
    }

    /**
     * Gibt Konfigurationswerte für Debugging-Zwecke zurück
     *
     * @return array
     */
    public function getConfigForDebugging(): array
    {
        return [
            'universe_size' => $this->config['universe_size'],
            'min_distance' => $this->config['min_distance'],
            'asteroid_min_distance' => $this->config['asteroid_min_distance'],
            'universe_border_distance' => $this->config['universe_border_distance'],
            'extreme_resource_distance' => $this->resourceConfig['resource_min_distances']['extreme_value'],
            'high_resource_distance' => $this->resourceConfig['resource_min_distances']['high_value'],
            'medium_resource_distance' => $this->resourceConfig['resource_min_distances']['medium_value'],
            'low_resource_distance' => $this->resourceConfig['resource_min_distances']['low_value'],
            'asteroid_count' => count($this->asteroidSpatialIndex),
            'resource_count' => count($this->resourceSpatialIndex),
            'station_count' => count($this->stations),
            'grid_size' => $this->gridSize
        ];
    }
}
