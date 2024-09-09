<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Asteroid;

class SetupInitialStation
{
    private $config;
    private $stations;
    private $asteroids;

    public function __construct()
    {
        $this->config = config('stations');
        $this->stations = $this->getStations();
        $this->asteroids = $this->getAsteroids();
    }

    public function create(int $userId, string $userName)
    {
        $coordinate = $this->generateStationCoordinate();

        return Station::create([
            'user_id' => $userId,
            'name' => $userName,
            'x' => $coordinate['x'],
            'y' => $coordinate['y'],
        ]);
    }

    public function generateStationCoordinate(): array
    {
        $minDistance = $this->config['min_distance'];
        $asteroidMinDistance = $this->config['asteroid_min_distance'];
        $universeBorderDistance = $this->config['universe_border_distance'];
        $universeSize = $this->config['universe_size'];
        $maxAttempts = 5000;
        $attempts = 0;
        $x = $y = 0;

        do {
            $x = rand($universeBorderDistance, $universeSize - $universeBorderDistance);
            $y = rand($universeBorderDistance, $universeSize - $universeBorderDistance);

            $isValid = !$this->isCollidingWithOtherStation($x, $y, $minDistance) &&
                !$this->isCollidingWithAsteroid($x, $y, $asteroidMinDistance);

            $attempts++;
        } while (!$isValid && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts) {
            throw new \Exception("Konnte keine gültige Position für die Station finden.");
        }

        return ['x' => $x, 'y' => $y];
    }

    private function isCollidingWithOtherStation($x, $y, $minDistance): bool
    {
        foreach ($this->stations as $station) {
            if (
                abs($station['x'] - $x) < $minDistance &&
                abs($station['y'] - $y) < $minDistance
            ) {
                return true;
            }
        }

        return false;
    }

    private function isCollidingWithAsteroid(int $x, int $y, int $minDistance): bool
    {
        foreach ($this->asteroids as $asteroid) {
            if (
                abs($asteroid['x'] - $x) < $minDistance &&
                abs($asteroid['y'] - $y) < $minDistance
            ) {
                return true;
            }
        }

        return false;
    }

    protected function getStations()
    {
        return Station::all()->map(function ($station) {
            return [
                'id' => $station->id,
                'name' => $station->name,
                'x' => $station->x,
                'y' => $station->y,
            ];
        })->toArray();
    }

    protected function getAsteroids()
    {
        return Asteroid::all()->map(function ($asteroid) {
            return [
                'x' => $asteroid->x,
                'y' => $asteroid->y,
            ];
        })->toArray();
    }

}
