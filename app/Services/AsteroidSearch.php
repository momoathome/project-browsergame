<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\Station;
use App\Models\UserAttribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AsteroidSearch
{
  public function search($query): array
  {
    if (empty($query)) {
      return [[], []];
    }

    $userId = Auth::id();
    $userStation = Station::where('user_id', $userId)->first();
    $scanRange = $this->getUserScanRange($userId);

    // Suchparameter vorbereiten
    $params = $this->parseSearchQuery($query);

    // Räumliche Vorfilterung: Berechne die Koordinatenbereiche, in denen gesucht werden soll
    $spatialFilter = $this->createSpatialFilter($userStation->x, $userStation->y, $scanRange);

    // Meilisearch-Abfrage erstellen mit räumlichem Filter
    $meiliSearchQuery = $this->buildMeilisearchQuery(
      $params['searchTerms'],
      $params['expandedResourceFilters'],
      $params['hasCombinedResources'],
      $params['sizeFilter'],
      $spatialFilter
    );

    // Suche ausführen und Ressourcen laden
    $searchedAsteroids = $meiliSearchQuery->get()->load('resources');

    // Genaue Filterung nach Scan-Bereich (für kreisförmigen Radius)
    $filteredAsteroids = $searchedAsteroids->filter(function ($asteroid) use ($userStation, $scanRange) {
      $distance = $this->calculateDistance(
        $userStation->x,
        $userStation->y,
        $asteroid->x,
        $asteroid->y
      );
      return $distance <= $scanRange;
    })->values();

    $searchedStations = Station::search($query)->take(100)->get();

    return [$filteredAsteroids, $searchedStations];
  }

  /**
   * Erstellt einen räumlichen Filter basierend auf Koordinaten und Scanbereich
   */
  private function createSpatialFilter($centerX, $centerY, $scanRange): array
  {
    // Berechne das quadratische Suchgebiet (etwas größer als der kreisförmige Scanbereich)
    return [
      'minX' => $centerX - $scanRange,
      'maxX' => $centerX + $scanRange,
      'minY' => $centerY - $scanRange,
      'maxY' => $centerY + $scanRange
    ];
  }

  /**
   * Analysiert die Suchabfrage und extrahiert Parameter
   */
  private function parseSearchQuery(string $query): array
  {
    // Punkte als Leerzeichen behandeln
    $query = str_replace('.', ' ', $query);
    $queryParts = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
    
    $sizeFilter = null;
    $resourceFilters = [];
    $hasCombinedResources = false;
    $searchTerms = [];
    
    foreach ($queryParts as $part) {
      // Nach bekannten Größen-Keywords filtern
      if ($this->isSize($part)) {
        $sizeFilter = strtolower($part);
        continue;
      }
      
      // Nach kombinierten Ressourcen suchen (mit Trenner)
      if ($this->isCombinedResource($part)) {
        $hasCombinedResources = true;
        $resourceFilters = array_merge($resourceFilters, $this->splitCombinedResource($part));
        continue;
      }
      
      // Prüfen, ob es ein Ressourcen-Synonym ist
      if ($this->isResourceSynonym($part)) {
        $resourceFilters[] = $part;
        continue;
      }
      
      // Andernfalls als allgemeinen Suchterm behandeln
      $searchTerms[] = $part;
    }
    
    $expandedResourceFilters = $this->expandResources($resourceFilters);
    
    return [
      'sizeFilter' => $sizeFilter,
      'resourceFilters' => $resourceFilters,
      'hasCombinedResources' => $hasCombinedResources,
      'searchTerms' => $searchTerms,
      'expandedResourceFilters' => $expandedResourceFilters,
    ];
  }
  
  /**
   * Prüft, ob der Begriff eine Größenangabe ist
   */
  private function isSize(string $part): bool
  {
    return in_array(strtolower($part), ['small', 'medium', 'large', 'extreme']);
  }
  
  /**
   * Prüft, ob der Begriff eine kombinierte Ressource ist
   */
  private function isCombinedResource(string $part): bool
  {
    return strpos($part, '-') !== false || strpos($part, '_') !== false;
  }
  
  /**
   * Teilt eine kombinierte Ressource in einzelne Teile auf
   */
  private function splitCombinedResource(string $part): array
  {
    $separator = strpos($part, '-') !== false ? '-' : '_';
    $resources = explode($separator, $part);
    
    return array_filter($resources, function ($resource) {
      return !empty($resource);
    });
  }
  
  /**
   * Prüft, ob der Begriff ein bekanntes Ressourcen-Synonym ist
   */
  private function isResourceSynonym(string $part): bool
  {
    return !empty($this->expandResource($part));
  }

  /**
   * Erstellt eine optimierte Meilisearch-Abfrage basierend auf den Parametern
   */
  private function buildMeilisearchQuery(
    array $searchTerms,
    array $expandedResourceFilters,
    bool $hasCombinedResources,
    ?string $sizeFilter,
    array $spatialFilter
  ) {
    // Basissuche
    if (!empty($searchTerms)) {
      $meiliSearchQuery = Asteroid::search(implode(' ', $searchTerms));
    } elseif (!empty($expandedResourceFilters)) {
      // Ressourcensuche: Verwende Ressourcen als Suchbegriff UND als Filter
      // Dies kombiniert die Stärken von Volltextsuche und Filterung
      $meiliSearchQuery = Asteroid::search(implode(' ', $expandedResourceFilters));
    } else {
      // Fallback: Leere Suche
      $meiliSearchQuery = Asteroid::search('');
    }

    // Filteroptionen für Meilisearch
    $filterConditions = [];

    // Ressourcenfilter - verbesserte Version
    if (!empty($expandedResourceFilters)) {
      $resourceConditions = [];
      foreach ($expandedResourceFilters as $resource) {
        $resourceConditions[] = "resource_types = '{$resource}'";
      }

      // Bei kombinierten Ressourcen AND verwenden, sonst OR
      $operator = $hasCombinedResources ? ' AND ' : ' OR ';
      $resourceFilterString = implode($operator, $resourceConditions);

      // Gruppiere die Ressourcenfilterbedingungen
      $filterConditions[] = "({$resourceFilterString})";
    }

    if ($spatialFilter) {
      $filterConditions[] = "(x >= {$spatialFilter['minX']} AND x <= {$spatialFilter['maxX']} AND y >= {$spatialFilter['minY']} AND y <= {$spatialFilter['maxY']})";
    }
    // Größenfilter
    if ($sizeFilter) {
      $filterConditions[] = "size = '{$sizeFilter}'";
    }

    // Filter anwenden, wenn vorhanden
    if (!empty($filterConditions)) {
      $finalFilter = implode(' AND ', $filterConditions);

      // MeiliSearch-Optionen setzen
      $meiliSearchQuery->options([
        'filter' => $finalFilter,
        'limit' => 1000  // Meilisearch-Limit
      ]);
    } else {
      // Standardlimit setzen
      $meiliSearchQuery->take(1000);
    }

    return $meiliSearchQuery;
  }
  /**
   * Expandiert einen einzelnen Ressourcenbegriff
   */
  private function expandResource($term): array
  {
    $synonyms = $this->getResourceSynonyms();
    $termLower = strtolower($term);

    return isset($synonyms[$termLower]) ? $synonyms[$termLower] : [];
  }

  /**
   * Gibt den Scan-Bereich eines Benutzers zurück und cached das Ergebnis
   */
  private function getUserScanRange($userId): int
  {
    static $cachedScanRanges = [];

    if (!isset($cachedScanRanges[$userId])) {
      $scanRangeAttribute = UserAttribute::where('user_id', $userId)
        ->where('attribute_name', 'scan_range')
        ->first();

      $cachedScanRanges[$userId] = $scanRangeAttribute ? (int) $scanRangeAttribute->attribute_value : 5000;
    }

    return $cachedScanRanges[$userId];
  }

  private function calculateDistance($x1, $y1, $x2, $y2): float
  {
    return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
  }

  /**
   * Erweitert die Ressourcenfilter um deren Synonyme
   */
  private function expandResources(array $resourceFilter): array
  {
    if (empty($resourceFilter)) {
      return [];
    }
  
    $expandedResourceFilter = [];
    $synonyms = $this->getResourceSynonyms();
    
    foreach ($resourceFilter as $resource) {
      $resourceLower = strtolower($resource);
      $expandedResourceFilter = array_merge(
        $expandedResourceFilter,
        $synonyms[$resourceLower] ?? [$resource]
      );
    }
    
    return array_unique($expandedResourceFilter);
  }

  /**
   * Gibt ein Array mit allen Ressource-Synonymen zurück
   */
  public function getResourceSynonyms(): array
  {
    return [
      'car' => ['Carbon'],
      'carb' => ['Carbon'],
      'crab' => ['Carbon'],
      'ca' => ['Carbon'],
      'bon' => ['Carbon'],
      'arb' => ['Carbon'],

      'tit' => ['Titanium'],
      'tita' => ['Titanium'],
      'ti' => ['Titanium'],
      'tia' => ['Titanium'],
      'tti' => ['Titanium'],
      'tta' => ['Titanium'],

      'hydro' => ['Hydrogenium'],
      'hyd' => ['Hydrogenium'],
      'hdy' => ['Hydrogenium'],
      'hy' => ['Hydrogenium'],
      'hydo' => ['Hydrogenium'],
      'oge' => ['Hydrogenium'],
      'ogen' => ['Hydrogenium'],

      'kyber' => ['Kyberkristall'],
      'kyb' => ['Kyberkristall'],
      'ky' => ['Kyberkristall'],
      'ber' => ['Kyberkristall'],
      'kby' => ['Kyberkristall'],
      'kris' => ['Kyberkristall'],
      'kri' => ['Kyberkristall'],
      'kristal' => ['Kyberkristall'],
      'kristall' => ['Kyberkristall'],

      'cob' => ['Cobalt'],
      'co' => ['Cobalt'],
      'balt' => ['Cobalt'],
      'clt' => ['Cobalt'],

      'irid' => ['Iridium'],
      'iri' => ['Iridium'],
      'id' => ['Iridium'],
      'dium' => ['Iridium'],

      'ast' => ['Astatine'],
      'astat' => ['Astatine'],
      'as' => ['Astatine'],
      'tin' => ['Astatine'],

      'thor' => ['Thorium'],
      'th' => ['Thorium'],
      'tho' => ['Thorium'],
      'thori' => ['Thorium'],
      'hto' => ['Thorium'],
      'htor' => ['Thorium'],

      'ur' => ['Uraninite'],
      'uran' => ['Uraninite'],
      'urin' => ['Uraninite'],
      'rni' => ['Uraninite'],
      'ninite' => ['Uraninite'],
      'nite' => ['Uraninite'],
      'uranium' => ['Uraninite'],
      'uranite' => ['Uraninite'],
      'uarn' => ['Uraninite'],
      'ran' => ['Uraninite'],
      'arn' => ['Uraninite'],

      'hyp' => ['Hyperdiamond'],
      'hype' => ['Hyperdiamond'],
      'hyper' => ['Hyperdiamond'],
      'hyperd' => ['Hyperdiamond'],
      'dia' => ['Hyperdiamond'],
      'diamond' => ['Hyperdiamond'],
      'amon' => ['Hyperdiamond'],
      'amo' => ['Hyperdiamond'],

      'dili' => ['Dilithium'],
      'dilli' => ['Dilithium'],
      'thium' => ['Dilithium'],
      'thi' => ['Dilithium'],
      'dl' => ['Dilithium'],
      'tili' => ['Dilithium'],

      'deut' => ['Deuterium'],
      'riu' => ['Deuterium'],
      'deu' => ['Deuterium'],
      'rui' => ['Deuterium'],
      'dt' => ['Deuterium'],
      'dui' => ['Deuterium'],
      'ter' => ['Deuterium'],
      'eud' => ['Deuterium'],
      'edu' => ['Deuterium'],
    ];
  }
}
