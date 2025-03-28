<?php

namespace Orion\Modules\Asteroid\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Models\AsteroidResource;
use Orion\Modules\Asteroid\Services\UniverseService;
use Orion\Modules\Asteroid\Services\AsteroidService;

class AsteroidGenerator
{
  public function __construct(
    private readonly UniverseService $universeService,
    private readonly AsteroidService $asteroidService,
  ) {
    $this->initialize();
  }

  private array $asteroidConfig = [];
  private array $config = [];
  private array $stations = [];
  private array $existingAsteroids = [];
  private array $cachedAsteroidPositions = [];
  private array $spatialIndex = [];
  private int $gridSize = 1000;


  private function initialize(): void
  {
    $this->asteroidConfig = config('game.asteroids');
    $this->config = config('game.core');
    $this->stations = $this->universeService->getStations();
    $this->loadExistingAsteroids();
  }

  private function loadExistingAsteroids()
  {
    $asteroids = $this->asteroidService->getAllAsteroids();
    $this->existingAsteroids = $asteroids->toArray();

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
    // Reservierte Stationsstandorte prüfen oder erzeugen
    $reservedRegions = $this->universeService->getReservedStationRegions();
    if (empty($reservedRegions)) {
      \Log::info("Keine reservierten Stationsstandorte gefunden. Reserviere Standardanzahl...");
      $reservedRegions = $this->universeService->reserveStationRegions(25);
    }

/*     $strategicAsteroids = $this->config['strategic_asteroid_count'] ?? 20;
    // Zuerst strategische Asteroiden um Stationsstandorte platzieren
    $this->placeStrategicAsteroids($strategicAsteroids); */

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

        $coordinate = $this->generateAsteroidCoordinate($minStationDistance, $resources);
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
      $this->asteroidConfig['asteroid_faktor']['min'],
      $this->asteroidConfig['asteroid_faktor']['max']
    );
    $asteroidSize = $this->generateAsteroidSize($this->asteroidConfig['asteroid_size']);
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

  private function calculateMinStationDistance(string $size, array $resources): int
  {
    $baseDistance = $this->asteroidConfig['station_safety_distance']['base'];
    $sizeModifier = $this->asteroidConfig['station_safety_distance']["{$size}_asteroid"];
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
      foreach ($this->asteroidConfig['resource_pools'] as $poolName => $pool) {
        if (in_array($resourceType, $pool['resources'])) {
          $distance = $this->asteroidConfig['resource_min_distances'][$poolName] ?? 0;
          $maxDistance = max($maxDistance, $distance);
          break;
        }
      }
    }

    return $maxDistance;
  }

  private function generateAsteroidCoordinate(int $minStationDistance, array $resources = []): array
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

    // Bestimme Ressourcenlevel basierend auf den Ressourcen des Asteroiden
    $resourceLevel = $this->determineResourceLevel($resources);

    // Räumliche Aufteilung für effizientere Suche
    $regionSize = max($minDistance * 2, $minStationDistance);
    $regionsX = ceil(($spawnArea['max_x'] - $spawnArea['min_x']) / $regionSize);
    $regionsY = ceil(($spawnArea['max_y'] - $spawnArea['min_y']) / $regionSize);
    $triedRegions = [];

    do {
      // Nach vielen Versuchen systematischer vorgehen
      if ($attempts > 1000) {
        // Zufällige Region wählen, die noch nicht voll versucht wurde
        $rx = rand(0, $regionsX - 1);
        $ry = rand(0, $regionsY - 1);
        $regionKey = "{$rx}:{$ry}";

        if (isset($triedRegions[$regionKey]) && $triedRegions[$regionKey] > 10) {
          $attempts++;
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

      // Überprüfungen für die Position
      $isAllowedForAsteroid = true;

      // 1. Keine Asteroiden im inneren Ring oder im äußeren Ring basierend auf Ressourcenlevel
      if ($this->isInReservedStationRegion($x, $y, $resourceLevel)) {
        $isAllowedForAsteroid = false;
      }

      // 2. Standard-Validierungen: Abstand zu Stationen und anderen Asteroiden
      if ($isAllowedForAsteroid) {
        $isAllowedForAsteroid = !$this->isCollidingWithStation($x, $y, $minStationDistance) &&
          !$this->isCollidingWithAsteroid($x, $y, $asteroidToAsteroidDistance);
      }

      $attempts++;

      if ($attempts % 1000 === 0 && $minDistance > 100) {
        $minDistance = max(100, $minDistance * 0.8); // Reduziere den Mindestabstand bei Schwierigkeiten
      }

    } while (!$isAllowedForAsteroid && $attempts < $maxAttempts);

    if (!$isAllowedForAsteroid) {
      throw new \Exception("Konnte keine gültige Position finden nach {$attempts} Versuchen.");
    }

    return ['x' => (int) $x, 'y' => (int) $y];
  }

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

  public function isCollidingWithStation(int $x, int $y, int $minDistanceFromStation): bool
  {
      // 1. Prüfen auf Kollisionen mit existierenden Stationen über UniverseService
      $stations = $this->universeService->getStations();
      
      foreach ($stations as $station) {
          $distance = sqrt(pow($station['x'] - $x, 2) + pow($station['y'] - $y, 2));
          if ($distance < $minDistanceFromStation) {
              return true;
          }
      }
    
      // 2. Prüfen auf Kollisionen mit reservierten Stationsregionen
      $reservedRegions = $this->getReservedStationRegions();
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
  
  public function getReservedStationRegions(bool $forceRefresh = false): array
  {
      return $this->universeService->getReservedStationRegions($forceRefresh);
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
    return $this->asteroidConfig['asteroid_faktor_multiplier'][$size] ?? ['min' => 0, 'max' => 0];
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
    $poolWeights = $this->asteroidConfig['pool_weights'];
    $num_resource_range = $this->asteroidConfig['num_resource_range'];
    $resource_ratio_range = $this->asteroidConfig['resource_ratio_range'];
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
      $selected_pool = $this->asteroidConfig['resource_pools'][$selected_pool_name];
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
    return $this->asteroidConfig['asteroid_img_size'][$size] ?? $this->asteroidConfig['asteroid_img_size']['small'];
  }

  private function isInReservedStationRegion(int $x, int $y, string $resourceLevel = 'any'): bool
  {
      $reservedRegions = $this->getReservedStationRegions();
  
      foreach ($reservedRegions as $region) {
          $stationX = $region['station_x'] ?? $region['x'] ?? 0;
          $stationY = $region['station_y'] ?? $region['y'] ?? 0;
          $distance = sqrt(pow($stationX - $x, 2) + pow($stationY - $y, 2));
  
          // Im Kern sind gar keine Asteroiden erlaubt
          $innerRadius = $region['inner_radius'] ?? $this->config['station_inner_radius'] ?? 450;
          if ($distance <= $innerRadius) {
              return true;
          }
  
          // Im äußeren Bereich sind nur Low-Value erlaubt
          $outerRadius = $region['outer_radius'] ?? $this->config['station_outer_radius'] ?? 4000;
          if ($distance <= $outerRadius && $resourceLevel !== 'low') {
              return true;
          }
      }
  
      return false;
  }

  private function determineResourceLevel(array $resources): string
  {
    if (empty($resources)) {
      return 'low'; // Default-Level für leere Asteroiden
    }

    // Ressourcenpools nach Level kategorisieren
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

    // Höchstes Level suchen
    $highestLevel = 'low';
    $levelRanking = ['low' => 1, 'medium' => 2, 'high' => 3];

    // Für jede Ressource den zugehörigen Pool und dessen Level finden
    foreach ($resources as $resourceType => $amount) {
      foreach ($this->asteroidConfig['resource_pools'] as $poolName => $pool) {
        if (in_array($resourceType, $pool['resources'])) {
          $level = $valueHints[$poolName];
          // Wenn das Level höher ist als das bisher gefundene, aktualisieren
          if ($levelRanking[$level] > $levelRanking[$highestLevel]) {
            $highestLevel = $level;
          }
          break;
        }
      }
    }

    return $highestLevel;
  }

  public function placeStrategicAsteroids(int $asteroidsPerStation = 12): array
  {
    $reservedRegions = $this->getReservedStationRegions();
    if (empty($reservedRegions)) {
      \Log::warning("Keine reservierten Stationsstandorte gefunden. Überspringe strategische Asteroiden-Platzierung.");
      return [];
    }

    $createdAsteroids = [];
    $strategicAsteroidBatch = [];
    $strategicResourcesBatch = [];

    foreach ($reservedRegions as $region) {
      $stationX = $region['station_x'];
      $stationY = $region['station_y'];
      $innerRadius = $region['inner_radius'];
      $outerRadius = $region['outer_radius'];

      // Erstelle eine Asteroiden-Verteilung um die Station
      for ($i = 0; $i < $asteroidsPerStation; $i++) {
        // Zufällige Position im Ring zwischen innerem und äußerem Radius
        $angle = rand(0, 360) * M_PI / 180;

        // Wurzelfunktion für gleichmäßigere Flächenverteilung
        $randomFactor = sqrt(rand(1, 10000) / 10000);
        $distance = $innerRadius + $randomFactor * ($outerRadius - $innerRadius);

        $x = (int) ($stationX + $distance * cos($angle));
        $y = (int) ($stationY + $distance * sin($angle));

        // Sicherstellen, dass wir innerhalb des Universums sind
        $universeSize = $this->config['size'];
        $borderDistance = $this->config['border_distance'] ?? ($universeSize / 10);

        $x = max($borderDistance, min($universeSize - $borderDistance, $x));
        $y = max($borderDistance, min($universeSize - $borderDistance, $y));

        // Prüfen, ob genug Abstand zu anderen Asteroiden eingehalten wird
        $minDistance = $this->config['asteroid_distance'];
        if ($this->isCollidingWithAsteroid($x, $y, $minDistance)) {
          continue; // Überspringen, wenn zu nahe an anderen Asteroiden
        }

        // Je nach Entfernung unterschiedliche Größe wählen
        $distanceFactor = ($distance - $innerRadius) / ($outerRadius - $innerRadius);

        // Wahrscheinlichkeit für kleine und mittlere Asteroiden
        $sizeWeights = [
          'small' => 80 * $distanceFactor,
          'medium' => 20 * $distanceFactor,
          'large' => 0, // Keine großen Asteroiden in der Nähe von Stationen
          'extreme' => 0, // Keine extremen Asteroiden in der Nähe von Stationen
        ];

        // Zufällige Größe basierend auf den Gewichtungen wählen
        $totalWeight = array_sum($sizeWeights);
        $random = rand(0, $totalWeight - 1);
        $size = 'small'; // Standardgröße

        $cumulative = 0;
        foreach ($sizeWeights as $sizeType => $weight) {
          $cumulative += $weight;
          if ($random < $cumulative) {
            $size = $sizeType;
            break;
          }
        }

        // Erstelle nur Asteroiden mit Low-Value-Ressourcen
        $numResources = rand($this->asteroidConfig['num_resource_range'][0], $this->asteroidConfig['num_resource_range'][1]);
        $lowValuePool = $this->asteroidConfig['resource_pools']['low_value']['resources'];

        $resourceMap = [];
        for ($j = 0; $j < $numResources; $j++) {
          $resourceType = $lowValuePool[array_rand($lowValuePool)];
          $resourceRatio = rand($this->asteroidConfig['resource_ratio_range'][0], $this->asteroidConfig['resource_ratio_range'][1]);

          if (!isset($resourceMap[$resourceType])) {
            $resourceMap[$resourceType] = 0;
          }
          $resourceMap[$resourceType] += $resourceRatio;
        }

        $asteroidBaseFaktor = $this->generateAsteroidBaseFaktor(
          $this->asteroidConfig['asteroid_faktor']['min'],
          $this->asteroidConfig['asteroid_faktor']['max']
        );
        $asteroidFaktorMultiplier = $this->generateAsteroidFaktorMultiplier($size);
        $asteroidBaseMultiplier = $this->generateAsteroidBaseMultiplier($asteroidFaktorMultiplier);
        $asteroidValue = $this->generateAsteroidValue($asteroidBaseFaktor, $asteroidBaseMultiplier);
        $asteroidName = $this->generateAsteroidName($size, $asteroidValue, $asteroidBaseMultiplier);

        $asteroid = [
          'name' => $asteroidName,
          'size' => $size,
          'base' => $asteroidBaseFaktor,
          'multiplier' => $asteroidBaseMultiplier,
          'value' => $asteroidValue,
          'x' => $x,
          'y' => $y,
          'pixel_size' => $this->transformAsteroidImgSize($size),
        ];

        $strategicAsteroidBatch[] = $asteroid;
        $strategicResourcesBatch[] = $resourceMap;

        // Zum räumlichen Index temporär hinzufügen
        $this->addToSpatialIndex($x, $y, 'strategic-' . $region['id'] . '-' . $i);
      }
    }

    // Speichere die strategischen Asteroiden in der Datenbank
    if (!empty($strategicAsteroidBatch)) {
      $created = $this->saveBatchedAsteroids($strategicAsteroidBatch);
      $this->saveBatchedResources($created, $strategicResourcesBatch);
      $createdAsteroids = $created;
    }

    \Log::info("Erfolgreich " . count($createdAsteroids) . " strategische Asteroiden um reservierte Stationsstandorte platziert");

    return $createdAsteroids;
  }
}
