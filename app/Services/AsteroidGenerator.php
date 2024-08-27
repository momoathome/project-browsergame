<?php

namespace App\Services;

use App\Models\Asteroid;

class AsteroidGenerator
{
  protected $config;
  protected $stations;

  public function __construct()
  {
    $this->config = config('asteroids');
    // $this->stations = $this->getStations();

    $this->stations = [
      ['id' => 1, 'x' => 15000, 'y' => 10000, 'name' => 'Station 1'],
      ['id' => 2, 'x' => 30000, 'y' => 30000, 'name' => 'Station 2'],
      ['id' => 3, 'x' => 40000, 'y' => 20000, 'name' => 'Station 3'],
    ];
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
    $resources = $this->generateResourcesFromPools($asteroidValue);
    $asteroidName = $this->generateAsteroidName($asteroidRarity, $asteroidValue, $asteroidBaseMultiplier);

    return [
      'name' => $asteroidName,
      'rarity' => $asteroidRarity,
      'base' => $asteroidBaseFaktor,
      'multiplier' => $asteroidBaseMultiplier,
      'value' => $asteroidValue,
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

    return $resources;
  }

  private function generateAsteroidName(string $rarity, int $value, float $multiplier): string
  {
    $prefix = substr($rarity, 0, 2);
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

  private function transformAsteroidRarityToImgSize(string $rarity): int
  {
    return $this->config['asteroid_size'][$rarity] ?? $this->config['asteroid_size']['uncommen'];
  }
}
