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

    $queryParts = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

    $searchedAsteroids = $this->searchAsteroids($query, $queryParts);
    $searchedStations = $this->searchStations($query);

    return [$searchedAsteroids, $searchedStations];
  }

  private function searchAsteroids($query, $queryParts): Collection
  {
    $userId = Auth::id();
    $userStation = Station::where('user_id', $userId)->first();
    $scanRange = $this->getUserScanRange($userId);

    if ($this->isSingleWordQuery($queryParts)) {
      $searchResults = $this->performMeilisearchQuery($query);
    } else {
      $searchResults = $this->performComplexQuery($queryParts);
    }

    // Filterung der Ergebnisse nach Scan-Bereich
    return $searchResults->filter(function ($asteroid) use ($userStation, $scanRange) {
      $distance = $this->calculateDistance(
        $userStation->x,
        $userStation->y,
        $asteroid->x,
        $asteroid->y
      );
      return $distance <= $scanRange;
    })->values();
  }

  private function searchStations($query): Collection
  {
    return Station::search($query)->take(100)->get();
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

  private function isSingleWordQuery($queryParts): bool
  {
    return count($queryParts) === 1 && strpos($queryParts[0], '-') === false;
  }

  private function performMeilisearchQuery($query): Collection
  {
    return Asteroid::search($query)->take(1000)->get();
  }

  private function performComplexQuery($queryParts): Collection
  {
    $searchedAsteroids = Asteroid::query();
    $this->applySizeFilter($searchedAsteroids, $queryParts);
    $this->applyResourceFilter($searchedAsteroids, $queryParts);
    return $searchedAsteroids->take(1000)->get();
  }

  private function applySizeFilter($query, $queryParts): void
  {
    $rarities = ['small', 'medium', 'large', 'extreme'];
    foreach ($queryParts as $part) {
      if (in_array($part, $rarities)) {
        $query->where('size', $part);
        break;
      }
    }
  }

  private function applyResourceFilter($query, $queryParts): void
  {
    $resourceFilter = array_diff($queryParts, ['small', 'medium', 'large', 'extreme']);
    if (empty($resourceFilter)) {
      return;
    }

    $combinedResources = $this->getCombinedResources($resourceFilter);
    $expandedResourceFilter = $this->expandResources(array_diff($resourceFilter, $combinedResources));

    $this->applyCombinedResourcesFilter($query, $combinedResources);
    $this->applyExpandedResourceFilter($query, $expandedResourceFilter);
  }

  private function applyCombinedResourcesFilter($query, $combinedResources): void
  {
    foreach ($combinedResources as $combinedResource) {
      $resources = explode('-', $combinedResource);
      $expandedResources = $this->expandResources($resources);
      $query->whereHas('resources', function ($subQuery) use ($expandedResources) {
        $subQuery->whereIn('resource_type', $expandedResources);
      }, '=', count($expandedResources));
    }
  }

  private function applyExpandedResourceFilter($query, $expandedResourceFilter): void
  {
    if (!empty($expandedResourceFilter)) {
      $query->whereHas('resources', function ($subQuery) use ($expandedResourceFilter) {
        $subQuery->whereIn('resource_type', $expandedResourceFilter);
      });
    }
  }

  private function getCombinedResources($resourceFilter): array
  {
    return array_filter($resourceFilter, function ($item) {
      return strpos($item, '-') !== false;
    });
  }

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

  private function getResourceSynonyms(): array
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
