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
        $request->validate([
            'query' => 'required|string',
        ]);

        $query = $request->input('query');
        $queryParts = explode(' ', $query);

        $searchedAsteroids = Asteroid::query();

        // single word query with Meilisearch
        if (count($queryParts) === 1) {
            $searchedAsteroids = Asteroid::search($query)->take(1000)->get();
        } else {
            // Filter by rarity
            $rarityFilter = null;
            foreach ($queryParts as $part) {
                if (in_array($part, ['common', 'uncommen', 'rare', 'extreme'])) {
                    $rarityFilter = $part;
                    $searchedAsteroids->where('rarity', $rarityFilter);
                }
            }

            // Filter by resources
            $resourceFilter = array_diff($queryParts, ['common', 'uncommen', 'rare', 'extreme']);

            if (!empty($resourceFilter)) {
                $searchedAsteroids->whereHas('resources', function ($query) use ($resourceFilter) {
                    $query->whereIn('resource_type', $resourceFilter);
                }, '=', count($resourceFilter));
            }

            $searchedAsteroids = $searchedAsteroids->take(1000)->get();
        }

        // Filter by resources
/*         $resourceFilter = [];
        foreach ($queryParts as $part) {
            if (!in_array($part, ['common', 'uncommen', 'rare', 'extreme'])) {
                $resourceFilter[] = $part;
                Log::info('Filtering by resource: ' . $part);
            }
        }

        if (!empty($resourceFilter)) {
            if (count($resourceFilter) === 1) {
                $searchedAsteroids = $searchedAsteroids->filter(function ($asteroid) use ($resourceFilter) {
                    $resources = $asteroid->resources->pluck('resource_type')->toArray();
                    Log::info('Filtering by resource if resourcefilter is 1: ' . $resourceFilter[0]);
                    return in_array($resourceFilter[0], $resources);
                });
            } else {
                $searchedAsteroids = $searchedAsteroids->filter(function ($asteroid) use ($resourceFilter) {
                    $resources = $asteroid->resources->pluck('resource_type')->toArray();
                    Log::info('Filtering by resource if resourcefilter is > 1: ' . implode(', ', $resourceFilter));
                    return count(array_intersect($resourceFilter, $resources)) === count($resourceFilter);
                });
            }
            Log::info('Filtering by resources: ' . implode(', ', $resourceFilter));
        } */

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

