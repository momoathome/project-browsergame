<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Asteroid;
use Illuminate\Support\Facades\Cache;

class SetupInitialStation
{
    private $config;
    private $stations;
    private $asteroidSpatialIndex = [];
    private $gridSize = 2000;

    public function __construct()
    {
        $this->config = config('game.stations');
        $this->stations = $this->getStations();
        $this->buildAsteroidSpatialIndex();
    }

    public function create(int $userId, string $userName)
    {
        $coordinate = $this->generateStationCoordinate();

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

        do {
            // Intelligenterer Ansatz nach vielen Versuchen
            if ($attempts > 500) {
                $rx = rand(0, $regionsX - 1);
                $ry = rand(0, $regionsY - 1);
                $regionKey = "{$rx}:{$ry}";

                // Region überspringen, wenn sie bereits oft versucht wurde
                if (isset($triedRegions[$regionKey]) && $triedRegions[$regionKey] > 5) {
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
                !$this->isCollidingWithAsteroid($x, $y, $asteroidMinDistance);

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

    public static function clearCache()
    {
        Cache::forget('setup-station:stations');
        Cache::forget('setup-station:asteroid-spatial-index');
    }
}
