<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\Station;
use App\Models\AsteroidResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AsteroidGenerator
{
  protected $config;
  protected $stations;
  protected $existingAsteroids;
  protected $cachedAsteroidPositions = [];
  protected $spatialIndex = [];
  protected $gridSize = 1000;

  public function __construct()
  {
    $this->config = config('game.asteroids');
    $this->stations = $this->getStations();
    $this->loadExistingAsteroids();
  }

  private function loadExistingAsteroids()
  {
    $asteroids = Asteroid::all(['id', 'x', 'y']);
    $this->existingAsteroids = $asteroids;

    foreach ($asteroids as $asteroid) {
      $this->cachedAsteroidPositions[] = [
        'id' => $asteroid->id,
        'x' => $asteroid->x,
        'y' => $asteroid->y
      ];

      // Zum räumlichen Index hinzufügen
      $this->addToSpatialIndex($asteroid->x, $asteroid->y, $asteroid->id);
    }
  }

  private function addToSpatialIndex($x, $y, $id)
  {
    $gridX = floor($x / $this->gridSize);
    $gridY = floor($y / $this->gridSize);
    $key = "{$gridX}:{$gridY}";

    if (!isset($this->spatialIndex[$key])) {
      $this->spatialIndex[$key] = [];
    }

    $this->spatialIndex[$key][] = ['id' => $id, 'x' => $x, 'y' => $y];
  }

  private function addAsteroidToCache($asteroid)
  {
    // Unterstützt sowohl Arrays als auch Asteroid-Objekte
    if ($asteroid instanceof Asteroid) {
      $this->cachedAsteroidPositions[] = [
        'id' => $asteroid->id,
        'x' => $asteroid->x,
        'y' => $asteroid->y
      ];
    } else {
      $this->cachedAsteroidPositions[] = [
        'id' => $asteroid['id'],
        'x' => $asteroid['x'],
        'y' => $asteroid['y']
      ];
    }

    // Füge zum räumlichen Index hinzu
    $this->addToSpatialIndex(
      $asteroid instanceof Asteroid ? $asteroid->x : $asteroid['x'],
      $asteroid instanceof Asteroid ? $asteroid->y : $asteroid['y'],
      $asteroid instanceof Asteroid ? $asteroid->id : $asteroid['id']
    );
  }

  public function generateAsteroids($count)
  {
    $asteroids = [];
    $maxFailures = $count * 0.1;
    $failures = 0;
    $batchSize = 100; // Batch-Größe für Inserts
    $asteroidBatch = [];
    $resourceBatch = [];

    for ($i = 0; $i < $count; $i++) {
      try {
        $asteroid = $this->generateAsteroid();
        $resources = $this->generateResourcesFromPools($asteroid['value'], $asteroid['size']);
        $resourceBatch[] = $resources;

        $minStationDistance = $this->calculateMinStationDistance($asteroid['size'], $resources);

        $coordinate = $this->generateAsteroidCoordinate($minStationDistance);
        $asteroid['x'] = $coordinate['x'];
        $asteroid['y'] = $coordinate['y'];

        $asteroidBatch[] = $asteroid;

        if (count($asteroidBatch) >= $batchSize) {
          $createdAsteroids = $this->saveBatchedAsteroids($asteroidBatch);
          $this->saveBatchedResources($createdAsteroids, $resourceBatch);

          // Cache aktualisieren und Arrays zurücksetzen
          foreach ($createdAsteroids as $created) {
            $this->addAsteroidToCache($created);
            $asteroids[] = $created;
          }

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
        // Versuche erneut
        $i--;
        Log::warning("Versuche erneute generierung für Versuch {$i}, um Fehler zu beheben."); 
      }
    }

    if (count($asteroidBatch) > 0) {
      $createdAsteroids = $this->saveBatchedAsteroids($asteroidBatch);
      $this->saveBatchedResources($createdAsteroids, $resourceBatch);

      foreach ($createdAsteroids as $created) {
        $this->addAsteroidToCache($created);
        $asteroids[] = $created;
      }
    }

    return $asteroids;
  }

  private function saveBatchedAsteroids(array $asteroids): array
  {
    $success = Asteroid::insert($asteroids);

    if (!$success) {
      throw new \Exception("Fehler beim Speichern der Asteroiden");
    }

    $names = array_column($asteroids, 'name');
    $createdAsteroids = Asteroid::whereIn('name', $names)->get()->toArray();

    return $createdAsteroids;
  }

  private function saveBatchedResources(array $asteroids, array $resourcesData): void
  {
    $resourcesForInsert = [];

    // Referenzieren Sie die Ressourcen über den Index des Asteroiden
    foreach ($asteroids as $index => $asteroid) {
      if (!isset($resourcesData[$index])) {
        continue; // Keine Ressourcendaten für diesen Asteroiden
      }

      foreach ($resourcesData[$index] as $resourceType => $amount) {
        $resourcesForInsert[] = [
          'asteroid_id' => $asteroid['id'],
          'resource_type' => $resourceType,
          'amount' => $amount
        ];
      }
    }

    if (count($resourcesForInsert) > 0) {
      AsteroidResource::insert($resourcesForInsert);
    }
  }

  private function generateAsteroid(): array
  {
    $asteroidBaseFaktor = $this->generateAsteroidBaseFaktor(
      $this->config['asteroid_faktor']['min'],
      $this->config['asteroid_faktor']['max']
    );
    $asteroidSize = $this->generateAsteroidSize($this->config['asteroid_size']);
    $asteroidFaktorMultiplier = $this->generateAsteroidFaktorMultiplier($asteroidSize);
    $asteroidBaseMultiplier = $this->generateAsteroidBaseMultiplier($asteroidFaktorMultiplier);
    $asteroidValue = $this->generateAsteroidValue($asteroidBaseFaktor, $asteroidBaseMultiplier);
    $asteroidName = $this->generateAsteroidName($asteroidSize, $asteroidValue, $asteroidBaseMultiplier);

    return [
      'name' => $asteroidName,
      'size' => $asteroidSize,
      'base' => $asteroidBaseFaktor,
      'multiplier' => $asteroidBaseMultiplier,
      'value' => $asteroidValue,
      'pixel_size' => $this->transformAsteroidImgSize($asteroidSize),
    ];
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

  private function calculateMinStationDistance(string $size, array $resources): int
  {
    $baseDistance = $this->config['station_safety_distance']['base'];
    $sizeModifier = $this->config['station_safety_distance']["{$size}_asteroid"];
    $distance = $baseDistance * $sizeModifier;

    $resourceDistance = $this->getResourceMinDistance($resources);

    return max($distance, $resourceDistance);
  }

  private function getResourceMinDistance(array $resources): int
  {
    if (empty($resources)) {
      return 0;
    }

    $maxDistance = 0;

    foreach ($resources as $resourceType => $amount) {
      foreach ($this->config['resource_pools'] as $poolName => $pool) {
        if (in_array($resourceType, $pool['resources'])) {
          $distance = $this->config['resource_min_distances'][$poolName] ?? 0;
          $maxDistance = max($maxDistance, $distance);
          break;
        }
      }
    }

    return $maxDistance;
  }

  protected function getStations()
  {
    return \Cache::remember('asteroid-generator:stations', 3600, function () {
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

  private function generateAsteroidCoordinate(int $minStationDistance): array
  {
    $asteroidToAsteroidDistance = $this->config['min_distance_between_asteroids'];
    $minDistance = $this->config['asteroid_to_station_distance'];
    $spawnArea = $this->config['spawn_area'];
    $maxAttempts = 5000;
    $attempts = 0;

    $regionSize = max($minDistance * 2, $minStationDistance);
    $regionsX = ceil(($spawnArea['max_x'] - $spawnArea['min_x']) / $regionSize);
    $regionsY = ceil(($spawnArea['max_y'] - $spawnArea['min_y']) / $regionSize);
    $triedRegions = [];

    do {
      // Nach den ersten 1000 Versuchen intelligenter vorgehen
      if ($attempts > 1000) {
        // Zufällige Region wählen, die noch nicht voll versucht wurde
        $rx = rand(0, $regionsX - 1);
        $ry = rand(0, $regionsY - 1);
        $regionKey = "{$rx}:{$ry}";

        if (isset($triedRegions[$regionKey]) && $triedRegions[$regionKey] > 10) {
          continue; // Diese Region scheint bereits gut überprüft
        }

        $triedRegions[$regionKey] = ($triedRegions[$regionKey] ?? 0) + 1;

        // Innerhalb dieser Region eine Position wählen
        $x = $spawnArea['min_x'] + ($rx * $regionSize) + rand(0, $regionSize);
        $y = $spawnArea['min_y'] + ($ry * $regionSize) + rand(0, $regionSize);
      } else {
        // In den ersten 1000 Versuchen vollständig zufällig vorgehen
        $x = rand($spawnArea['min_x'], $spawnArea['max_x']);
        $y = rand($spawnArea['min_y'], $spawnArea['max_y']);
      }

      $isValid = !$this->isCollidingWithStation($x, $y, $minStationDistance) &&
        !$this->isCollidingWithAsteroid($x, $y, $asteroidToAsteroidDistance);

      $attempts++;

      if ($attempts % 1000 === 0 && $minDistance > 100) {
        $minDistance = max(100, $minDistance * 0.8);
      }

    } while (!$isValid && $attempts < $maxAttempts);

    if (!$isValid) {
      throw new \Exception("Konnte keine gültige Position finden nach {$attempts} Versuchen.");
    }

    return ['x' => $x, 'y' => $y];
  }

  private function isCollidingWithAsteroid(int $x, int $y, int $minDistance): bool
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

        foreach ($this->spatialIndex[$key] as $asteroid) {
          $distance = sqrt(pow($asteroid['x'] - $x, 2) + pow($asteroid['y'] - $y, 2));
          if ($distance < $minDistance) {
            return true;
          }
        }
      }
    }

    return false;
  }

  private function isCollidingWithStation(int $x, int $y, int $minDistanceFromStation): bool
  {
    foreach ($this->stations as $station) {
      $distance = sqrt(pow($station['x'] - $x, 2) + pow($station['y'] - $y, 2));
      if ($distance < $minDistanceFromStation) {
        return true;
      }
    }
    return false;
  }

  private function generateAsteroidBaseFaktor(int $min, int $max): int
  {
    return rand($min, $max);
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

  private function generateAsteroidFaktorMultiplier(string $size): array
  {
    return $this->config['asteroid_faktor_multiplier'][$size] ?? ['min' => 0, 'max' => 0];
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

  private function generateResourcesFromPools($asteroidValue, string $size): array
  {
    // Konfiguration auslesen
    $poolWeights = $this->config['pool_weights'];
    $num_resource_range = $this->config['num_resource_range'];
    $resource_ratio_range = $this->config['resource_ratio_range'];
    $num_resources = rand($num_resource_range[0], $num_resource_range[1]);

    // Wenn die Größe extrem ist, extreme und high Value Pools entfernen aus balance Gründen
    $adjustedPoolWeights = $poolWeights;
    if ($size === 'extreme') {
      unset($adjustedPoolWeights['extreme_value'], $adjustedPoolWeights['high_value']);

      // Korrekte Normalisierung der Gewichte
      $total = array_sum($adjustedPoolWeights);
      $adjustedPoolWeights = array_map(function ($weight) use ($total) {
        return $weight / $total;
      }, $adjustedPoolWeights);
    }

    $resource_ratios = [];

    // Ressourcen auswählen und die Pools speichern
    for ($i = 0; $i < $num_resources; $i++) {
      $selected_pool_name = $this->getRandomPool($adjustedPoolWeights);
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
    if ($total_ratio <= 0) {
      return [];
    }

    // Tatsächliche Ressourcenmengen berechnen
    $resources_with_values = [];
    foreach ($resource_ratios as $resource => $ratio) {
      // Ressourcenwert basierend auf dem Verhältnis und dem Gesamtwert des Asteroiden berechnen
      $resources_with_values[$resource] = intval(($ratio / $total_ratio) * $asteroidValue);
    }

    return $resources_with_values;
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

  private function transformAsteroidImgSize(string $size): int
  {
    return $this->config['asteroid_img_size'][$size] ?? $this->config['asteroid_img_size']['small'];
  }
}
