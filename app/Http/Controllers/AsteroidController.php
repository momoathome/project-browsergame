<?php

namespace App\Http\Controllers;

use App\Models\Asteroid;
use App\Models\Station;
use App\Services\AsteroidExplorer;
use App\Services\AsteroidSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Spacecraft;
use App\Http\Requests\AsteroidExploreRequest;
use Illuminate\Support\Facades\Log;


class AsteroidController extends Controller
{
    protected $asteroidExplorer;
    protected $asteroidSearch;

    public function __construct(AsteroidExplorer $asteroidExplorer, AsteroidSearch $asteroidSearch)
    {
        $this->asteroidExplorer = $asteroidExplorer;
        $this->asteroidSearch = $asteroidSearch;
    }

    public function index()
    {
/*         $asteroids = Asteroid::with('resources')->get();
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
        ]); */

        return $this->renderAsteroidMap();

    }

    public function update(AsteroidExploreRequest $request)
    {
        $user = auth()->user();
        $explorationResult = $this->asteroidExplorer->exploreWithRequest($user, $request);

        if (!$explorationResult->wasSuccessful()) {
            return response()->json(['message' => 'Keine Ressourcen extrahiert'], 200);
        }

        return $this->renderAsteroidMap();
    }

    public function search(Request $request)
    {
        $request->validate(['query' => 'nullable|string']);
        $query = $request->input('query');
        if (empty($query)) {
            return $this->renderAsteroidMap();
        }

        [$searchedAsteroids, $searchedStations] = $this->asteroidSearch->search($query);

        return $this->renderAsteroidMap($searchedAsteroids, $searchedStations, null);
    }

    private function renderAsteroidMap($searchedAsteroids = [], $searchedStations = [], $selectedAsteroid = null)
    {
        // $asteroids = Asteroid::with('resources')->get();
         
        $asteroids = Asteroid::select('id', 'x', 'y', 'pixel_size')->get();
        $stations = Station::all();
        $user = auth()->user();
        $spacecrafts = Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('AsteroidMap', [
            'asteroids' => $asteroids,
            'searched_asteroids' => $searchedAsteroids,
            'searched_stations' => $searchedStations,
            'spacecrafts' => $spacecrafts,
            'stations' => $stations,
            'selected_asteroid' => $selectedAsteroid ?? null,
        ]);
    }

    public function getAsteroidResources(Asteroid $asteroid)
    {
        $asteroid->load(['resources']);

        return $this->renderAsteroidMap([], [], $asteroid);
    }

    public function universeResources()
    {
        $resources = Asteroid::with('resources')
            ->get()
            ->pluck('resources')
            ->flatten()
            ->groupBy('resource_type')
            ->map(function ($resources) {
                return [$resources->sum('amount')];
            });

        return Inertia::render('Admin/Dashboard', [
            'universeResources' => $resources,
        ]);
    }
}

