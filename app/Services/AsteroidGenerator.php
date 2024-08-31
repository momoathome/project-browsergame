<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\Station;


class AsteroidGenerator
{
  protected $config;
  protected $stations;

  public function __construct()
  {
    $this->config = config('asteroids');
    $this->stations = $this->getStations();
  }

  protected function getStations()
    {
        return Station::all()->map(function($station) {
            return [
                'id' => $station->id,
                'x' => $station->coordinate_x,
                'y' => $station->coordinate_y,
                'name' => $station->name
            ];
        })->toArray();
    }

  public function generateAsteroids($count)
  {
    $asteroids = [];

    for ($i = 0; $i < $count; $i++) {
      $asteroid = $this->generateAsteroid();
      $coordinate = $this->generateAsteroidCoordinate($asteroid);
      $asteroid['x'] = $coordinate['x'];
      $asteroid['y'] = $coordinate['y'];
      $asteroid['pixel_size'] = $this->transformAsteroidRarityToImgSize($asteroid['rarity']);

      Asteroid::create($asteroid);
      $asteroids[] = $asteroid;
    }

    return $asteroids;
  }

  private function generateAsteroid(): array
  {
    $asteroidBaseFaktor = $this->generateAsteroidBaseFaktor(
      $this->config['asteroid_faktor']['min'],
      $this->config['asteroid_faktor']['max']
    );
    $asteroidRarity = $this->generateAsteroidRarity($this->config['asteroid_rarity']);
    $asteroidRarityMultiplier = $this->generateAsteroidRarityMultiplier($asteroidRarity);
    $asteroidBaseMultiplier = $this->generateAsteroidBaseMultiplier($asteroidRarityMultiplier);
    $asteroidValue = $this->generateAsteroidValue($asteroidBaseFaktor, $asteroidBaseMultiplier);
    $resourceData = $this->generateResourcesFromPools($asteroidValue);
    $resources = $resourceData['resources'];
    $pool = $resourceData['pool'];
    $asteroidName = $this->generateAsteroidName($asteroidRarity, $asteroidValue, $asteroidBaseMultiplier, $pool);

    return [
      'name' => $asteroidName,
      'rarity' => $asteroidRarity,
      'base' => $asteroidBaseFaktor,
      'multiplier' => $asteroidBaseMultiplier,
      'value' => $asteroidValue,
      'resource_pool' => $pool,
      'resources' => json_encode($resources),
    ];
  }

  private function generateAsteroidCoordinate(array $asteroid): array
  {
    $minDistance = $this->config['min_distance'];
    $universeSize = $this->config['universe_size'];
    $distanceModifier = $this->config['distance_modifiers'][$asteroid['rarity']] ?? $minDistance;
    $maxAttempts = 1000;
    $attempts = 0;
    $x = $y = 0;

    do {
      $x = rand($distanceModifier, $universeSize);
      $y = rand($distanceModifier, $universeSize);

      $isValid = !$this->isCollidingWithStation($x, $y, $distanceModifier) &&
        !$this->isCollidingWithAsteroid($x, $y);

      $attempts++;
    } while (!$isValid && $attempts < $maxAttempts);

    if ($attempts >= $maxAttempts) {
      throw new \Exception("Konnte keine gültige Position für den Asteroiden finden.");
    }

    return ['x' => $x, 'y' => $y];
  }

  private function isCollidingWithAsteroid(int $x, int $y): bool
  {
      $minDistance = $this->config['min_distance'];
      $asteroids = Asteroid::all();
  
      foreach ($asteroids as $asteroid) {
          if (
              abs($asteroid->x - $x) < $minDistance &&
              abs($asteroid->y - $y) < $minDistance
          ) {
              return true;
          }
      }
  
      return false;
  }

  private function isCollidingWithStation($x, $y, $distanceModifier): bool
  {
      foreach ($this->stations as $station) {
          $stationDistance = $this->config['station_radius'] + $distanceModifier;
          if (
              abs($station['x'] - $x) < $stationDistance &&
              abs($station['y'] - $y) < $stationDistance
          ) {
              return true;
          }
      }
  
      return false;
  }

  private function generateAsteroidBaseFaktor(int $min, int $max): int
  {
    return rand($min, $max);
  }

  private function generateAsteroidRarity(array $asteroidRarity): string
  {
    $totalWeight = array_sum($asteroidRarity);
    $randomValue = rand(0, $totalWeight - 1);
    $cumulativeWeight = 0;

    foreach ($asteroidRarity as $rarity => $weight) {
      $cumulativeWeight += $weight;
      if ($randomValue < $cumulativeWeight) {
        return $rarity;
      }
    }

    return 'common';  // Fallback
  }

  private function generateAsteroidRarityMultiplier(string $rarity): array
  {
    return $this->config['asteroid_rarity_multiplier'][$rarity] ?? ['min' => 0, 'max' => 0];
  }

  private function generateAsteroidBaseMultiplier(array $asteroidRarityMultiplier): float
  {
    $asteroidBaseMultiplier = rand(
      $asteroidRarityMultiplier['min'] * 100,
      $asteroidRarityMultiplier['max'] * 100
    ) / 100;

    return round($asteroidBaseMultiplier, 4);
  }

  private function generateAsteroidValue(int $asteroidBaseFaktor, float $asteroidBaseMultiplier): int
  {
    return floor($asteroidBaseFaktor * $asteroidBaseMultiplier);
  }

  private function generateResourcesFromPools(int $asteroidValue): array
  {
    $resources = [];
    $pool = array_rand($this->config['resource_pools']);
    $poolResources = $this->config['resource_pools'][$pool];
    $resourceWeights = $this->config['pool_resource_weights'][$pool];
    $totalWeight = array_sum($resourceWeights);

    foreach ($poolResources as $resource) {
      $weight = $resourceWeights[$resource];
      $normalizedWeight = $weight / $totalWeight;
      $resources[$resource] = floor($normalizedWeight * $asteroidValue);
    }

    return [
      'resources' => $resources,
      'pool' => $pool
  ];
  }

  private function generateAsteroidName(string $rarity, int $value, float $multiplier, string $pool): string
  {
    $prefix = substr($rarity, 0, 2);
    $suffix = substr($pool, 0, 2);
    $randomString = $this->generateRandomString(2);
    $randomString2 = $this->generateRandomString(2);
    
    return "{$randomString}{$prefix}{$suffix}{$randomString2}{$value}-" . floor($multiplier);
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

  private function transformAsteroidRarityToImgSize(string $rarity): int
  {
    return $this->config['asteroid_size'][$rarity] ?? $this->config['asteroid_size']['uncommen'];
  }
}
