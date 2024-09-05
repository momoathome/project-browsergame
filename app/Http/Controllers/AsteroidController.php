<?php

namespace App\Http\Controllers;

use App\Models\Asteroid;
use App\Models\Station;
use App\Services\AsteroidExplorer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Spacecraft;
use Illuminate\Support\Facades\Log;


class AsteroidController extends Controller
{
    protected $asteroidExplorer;

    public function __construct(AsteroidExplorer $asteroidExplorer)
    {
        $this->asteroidExplorer = $asteroidExplorer;
    }

    public function index()
    {
        $asteroids = Asteroid::with('resources')->get();
        $stations = Station::all();
        $user = auth()->user();

        $spacecrafts = Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('AsteroidMap', [
            'asteroids' => $asteroids,
            'spacecrafts' => $spacecrafts,
            'stations' => $stations,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'asteroid_id' => 'required|exists:asteroids,id',
            'spacecrafts' => 'required|array',
        ]);

        $spaceCrafts = $validated['spacecrafts'];

        $user = auth()->user();
        $this->asteroidExplorer->exploreAsteroid($user, $validated['asteroid_id'], $spaceCrafts);
    }

    /*     public function search(Request $request)
        {
            $request->validate([
                'query' => 'required|string',
            ]);

            $query = $request->input('query');
            $searched_asteroids = Asteroid::search($query)
            ->take(1000)
            ->get();

            $asteroids = Asteroid::with('resources')->get();
            $stations = Station::all();
            $user = auth()->user();
            $spacecrafts = Spacecraft::with('details')
                ->where('user_id', $user->id)
                ->orderBy('id', 'asc')
                ->get();
        
            return Inertia::render('AsteroidMap', [
                'asteroids' => $asteroids,
                'searched_asteroids' => $searched_asteroids,
                'spacecrafts' => $spacecrafts,
                'stations' => $stations,
            ]);
        } */



    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string']);
        $query = $request->input('query');
        $queryParts = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        if ($this->isSingleWordQuery($queryParts)) {
            $searchedAsteroids = $this->performMeilisearchQuery($query);
        } else {
            $searchedAsteroids = $this->performComplexQuery($queryParts);
        }

        return $this->renderAsteroidMap($searchedAsteroids);
    }

    private function isSingleWordQuery($queryParts)
    {
        return count($queryParts) === 1 && strpos($queryParts[0], '-') === false;
    }

    private function performMeilisearchQuery($query)
    {
        return Asteroid::search($query)->take(1000)->get();
    }

    private function performComplexQuery($queryParts)
    {
        $searchedAsteroids = Asteroid::query();
        $this->applyRarityFilter($searchedAsteroids, $queryParts);
        $this->applyResourceFilter($searchedAsteroids, $queryParts);
        return $searchedAsteroids->take(1000)->get();
    }

    private function applyRarityFilter($query, $queryParts)
    {
        $rarities = ['common', 'uncommen', 'rare', 'extreme'];
        foreach ($queryParts as $part) {
            if (in_array($part, $rarities)) {
                $query->where('rarity', $part);
                break;
            }
        }
    }

    private function applyResourceFilter($query, $queryParts)
    {
        $resourceFilter = array_diff($queryParts, ['common', 'uncommen', 'rare', 'extreme']);
        if (empty($resourceFilter)) {
            return;
        }

        $combinedResources = $this->getCombinedResources($resourceFilter);
        $expandedResourceFilter = $this->expandResources(array_diff($resourceFilter, $combinedResources));

        $this->applyCombinedResourcesFilter($query, $combinedResources);
        $this->applyExpandedResourceFilter($query, $expandedResourceFilter);
    }

    private function applyCombinedResourcesFilter($query, $combinedResources)
    {
        foreach ($combinedResources as $combinedResource) {
            $resources = explode('-', $combinedResource);
            $expandedResources = $this->expandResources($resources);
            $query->whereHas('resources', function ($subQuery) use ($expandedResources) {
                $subQuery->whereIn('resource_type', $expandedResources);
            }, '=', count($expandedResources));
        }
    }

    private function applyExpandedResourceFilter($query, $expandedResourceFilter)
    {
        if (!empty($expandedResourceFilter)) {
            $query->whereHas('resources', function ($subQuery) use ($expandedResourceFilter) {
                $subQuery->whereIn('resource_type', $expandedResourceFilter);
            });
        }
    }

    private function getCombinedResources($resourceFilter)
    {
        return array_filter($resourceFilter, function ($item) {
            return strpos($item, '-') !== false;
        });
    }

    private function expandResources($resourceFilter)
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

    private function getResourceSynonyms()
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

    private function renderAsteroidMap($searchedAsteroids)
    {
        $asteroids = Asteroid::with('resources')->get();
        $stations = Station::all();
        $user = auth()->user();
        $spacecrafts = Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('AsteroidMap', [
            'asteroids' => $asteroids,
            'searched_asteroids' => $searchedAsteroids,
            'spacecrafts' => $spacecrafts,
            'stations' => $stations,
        ]);
    }

}

