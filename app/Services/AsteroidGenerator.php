<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\Station;
use App\Models\AsteroidResource;
use Illuminate\Support\Facades\Log;


class AsteroidGenerator
{
  protected $config;
  protected $stations;

  public function __construct()
  {
    $this->config = config('asteroids');
    $this->stations = $this->getStations();
  }

  public function generateAsteroids($count)
  {
    $asteroids = [];

    for ($i = 0; $i < $count; $i++) {
      $asteroidData = $this->generateAsteroid();
      $coordinate = $this->generateAsteroidCoordinate($asteroidData);
      $asteroidData['x'] = $coordinate['x'];
      $asteroidData['y'] = $coordinate['y'];
      $asteroidData['pixel_size'] = $this->transformAsteroidRarityToImgSize($asteroidData['rarity']);

      $asteroid = Asteroid::create($asteroidData);
      $this->saveAsteroidResources($asteroid, $asteroidData['resources']);

      $asteroids[] = $asteroid;
    }

    return $asteroids;
  }

  private function saveAsteroidResources(Asteroid $asteroid, array $resources)
  {
    foreach ($resources as $resourceType => $amount) {
      AsteroidResource::create([
        'asteroid_id' => $asteroid->id,
        'resource_type' => $resourceType,
        'amount' => $amount,
      ]);
    }
  }

  private function generateAsteroid(): array
  {
    $asteroidBaseFaktor = $this->generateAsteroidBaseFaktor(
      $this->config['asteroid_faktor']['min'],
      $this->config['asteroid_faktor']['max']
    );
    $asteroidRarity = $this->generateAsteroidRarity($this->config['asteroid_rarity']);
    $asteroidFaktorMultiplier = $this->generateAsteroidFaktorMultiplier($asteroidRarity);
    $asteroidBaseMultiplier = $this->generateAsteroidBaseMultiplier($asteroidFaktorMultiplier);
    $asteroidValue = $this->generateAsteroidValue($asteroidBaseFaktor, $asteroidBaseMultiplier);

    $resources = $this->generateResourcesFromPools($asteroidValue);
    $asteroidName = $this->generateAsteroidName($asteroidRarity, $asteroidValue, $asteroidBaseMultiplier);

    return [
      'name' => $asteroidName,
      'rarity' => $asteroidRarity,
      'base' => $asteroidBaseFaktor,
      'multiplier' => $asteroidBaseMultiplier,
      'value' => $asteroidValue,
      'resources' => $resources,
    ];
  }

  protected function getStations()
  {
    return Station::all()->map(function ($station) {
      return [
        'id' => $station->id,
        'x' => $station->coordinate_x,
        'y' => $station->coordinate_y,
        'name' => $station->name
      ];
    })->toArray();
  }

  private function generateAsteroidCoordinate(array $asteroid): array
  {
    $minDistance = $this->config['min_distance'];
    $universeSize = $this->config['universe_size'];
    $distanceModifier = $this->config['distance_modifiers'][$asteroid['rarity']] ?? 0;

    $resources = $asteroid['resources'];
    $resourceDistanceModifier = $this->calculateResourceDistanceModifier($resources);
    $distanceModifier += $resourceDistanceModifier;

    $maxAttempts = 5000;
    $attempts = 0;
    $x = $y = 0;

    do {
      $x = rand($minDistance, $universeSize);
      $y = rand($minDistance, $universeSize);

      $isValid = !$this->isCollidingWithStation($x, $y, $distanceModifier) &&
        !$this->isCollidingWithAsteroid($x, $y, $minDistance);

      $attempts++;
    } while (!$isValid && $attempts < $maxAttempts);

    if ($attempts >= $maxAttempts) {
      throw new \Exception("Konnte keine gültige Position für den Asteroiden finden.");
    }

    return ['x' => $x, 'y' => $y];
  }

  private function isCollidingWithAsteroid(int $x, int $y, int $minDistance): bool
  {
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
      if (
        abs($station['x'] - $x) < $distanceModifier &&
        abs($station['y'] - $y) < $distanceModifier
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

    return 'common';
  }

  private function generateAsteroidFaktorMultiplier(string $rarity): array
  {
    return $this->config['asteroid_faktor_multiplier'][$rarity] ?? ['min' => 0, 'max' => 0];
  }

  private function generateAsteroidBaseMultiplier(array $asteroidFaktorMultiplier): float
  {
    $asteroidBaseMultiplier = rand(
      $asteroidFaktorMultiplier['min'] * 100,
      $asteroidFaktorMultiplier['max'] * 100
    ) / 100;

    return round($asteroidBaseMultiplier, 4);
  }

  private function generateAsteroidValue(int $asteroidBaseFaktor, float $asteroidBaseMultiplier): int
  {
    return floor($asteroidBaseFaktor * $asteroidBaseMultiplier);
  }

  private function getRandomPool($pool_weights)
  {
    $rand = mt_rand() / mt_getrandmax();
    $cumulative = 0;
    foreach ($pool_weights as $pool => $weight) {
      $cumulative += $weight;
      if ($rand <= $cumulative) {
        return $pool;
      }
    }
    return array_key_first($pool_weights);
  }

  private function generateResourcesFromPools($asteroidValue)
  {
    // Konfiguration auslesen
    $pool_weights = $this->config['pool_weights'];
    $num_resource_range = $this->config['num_resource_range'];
    $resource_ratio_range = $this->config['resource_ratio_range'];
    $num_resources = rand($num_resource_range[0], $num_resource_range[1]);

    $resource_ratios = [];

    // Ressourcen auswählen und die Pools speichern
    for ($i = 0; $i < $num_resources; $i++) {
      $selected_pool_name = $this->getRandomPool($pool_weights);
      $selected_pool = $this->config['resource_pools'][$selected_pool_name];
      $resource = $selected_pool['resources'][array_rand($selected_pool['resources'])];

      // Initialisiere das Ressourcen-Verhältnis, falls noch nicht vorhanden
      if (!isset($resource_ratios[$resource])) {
        $resource_ratios[$resource] = 0;
      }

      // Ressourcen-Verhältnis zufällig erhöhen
      $resource_ratios[$resource] += mt_rand($resource_ratio_range[0], $resource_ratio_range[1]);
    }

    // Gesamtverhältnis berechnen
    $total_ratio = array_sum($resource_ratios);

    // Wenn die Summe der Verhältnisse mehr als 100% ist, normalisieren
    $normalization_factor = $total_ratio > 0 ? $total_ratio : 1;

    // Tatsächliche Ressourcenmengen berechnen
    $resources_with_values = [];
    foreach ($resource_ratios as $resource => $ratio) {
      // Ressourcenwert basierend auf dem Verhältnis und dem Gesamtwert des Asteroiden berechnen
      $resources_with_values[$resource] = intval(($ratio / $normalization_factor) * $asteroidValue);
    }

    return $resources_with_values;
  }

  private function calculateResourceDistanceModifier(array $resources): int
  {
    $maxModifier = 0;

    foreach ($resources as $resource => $amount) {
      foreach ($this->config['resource_pools'] as $pool) {
        if (in_array($resource, $pool['resources'])) {
          $maxModifier = max($maxModifier, $pool['resource_distance_modifier']);
          break;
        }
      }
    }

    return $maxModifier;
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
