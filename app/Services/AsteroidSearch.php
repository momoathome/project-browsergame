<?php

namespace App\Services;

use App\Models\Asteroid;
use App\Models\Station;
use App\Models\UserAttribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

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
    
    // Abfrageteile extrahieren
    $queryParts = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
    
    // Kombinierte Ressourcen (mit Bindestrichen) extrahieren und in einzelne Teile zerlegen
    $sizeFilter = null;
    $resourceFilters = [];
    
    foreach ($queryParts as $part) {
      if (in_array(strtolower($part), ['small', 'medium', 'large', 'extreme'])) {
        $sizeFilter = strtolower($part);
      } else if (strpos($part, '-') !== false) {
        // Kombinierte Ressourcen aufteilen
        $resources = explode('-', $part);
        foreach ($resources as $resource) {
          $resourceFilters[] = $resource;
        }
      } else {
        // Einzelne Ressource
        $resourceFilters[] = $part;
      }
    }
    
    // Erstelle die Basisabfrage für Meilisearch - ohne kombinierte Teile
    $searchQuery = implode(' ', array_diff($queryParts, $resourceFilters));
    if (empty(trim($searchQuery))) {
      $searchQuery = '*'; // Wildcard-Suche, wenn nur Filter vorhanden sind
    }
    
    $meiliSearchQuery = Asteroid::search($searchQuery)->take(50000);
    
    // Filter nach Größe anwenden
    if ($sizeFilter) {
      $meiliSearchQuery->where('size', $sizeFilter);
    }
    
    // Suche ausführen
    $searchedAsteroids = $meiliSearchQuery->get()->load('resources');
    
    // Bei Ressourcenfiltern manuelle Filterung anwenden
    if (!empty($resourceFilters)) {
      $expandedFilters = $this->expandResources($resourceFilters);
      $searchedAsteroids = $searchedAsteroids->filter(function($asteroid) use ($expandedFilters) {
        // Prüfe, ob der Asteroid alle spezifizierten Ressourcen hat
        foreach ($expandedFilters as $resource) {
          if (!$asteroid->resources->contains('resource_type', $resource)) {
            return false;
          }
        }
        return true;
      });
    }
    
    // Filterung nach Scan-Bereich
    $filteredAsteroids = $searchedAsteroids->filter(function ($asteroid) use ($userStation, $scanRange) {
      $distance = $this->calculateDistance(
        $userStation->x, $userStation->y,
        $asteroid->x, $asteroid->y
      );
      return $distance <= $scanRange;
    })->values();
    
    // Stationssuche wie bisher
    $searchedStations = Station::search($query)->take(100)->get();
    
    return [$filteredAsteroids, $searchedStations];
  }

  private function getUserScanRange($userId): int
  {
    $scanRangeAttribute = UserAttribute::where('user_id', $userId)
      ->where('attribute_name', 'scan_range')
      ->first();

    return $scanRangeAttribute ? (int) $scanRangeAttribute->attribute_value : 5000;
  }

  private function calculateDistance($x1, $y1, $x2, $y2): float
  {
    return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
  }

  /**
   * Erweitert die Ressourcenfilter um deren Synonyme
   */
  private function expandResources($resourceFilter): array
  {
    $expandedResourceFilter = [];
    $synonyms = $this->getResourceSynonyms();

    foreach ($resourceFilter as $resource) {
      $resourceLower = strtolower($resource);
      if (isset($synonyms[$resourceLower])) {
        $expandedResourceFilter = array_merge($expandedResourceFilter, $synonyms[$resourceLower]);
      } else {
        $expandedResourceFilter[] = $resource;
      }
    }

    return $expandedResourceFilter;
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
