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
        $queryParts = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        $searchedAsteroids = Asteroid::query();

        // single word query with Meilisearch
        if (count($queryParts) === 1 && strpos($queryParts[0], '-') === false) {
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
                // Check if any of the resource filters contain a hyphen
                $combinedResources = array_filter($resourceFilter, function ($item) {
                    return strpos($item, '-') !== false;
                });

                if (!empty($combinedResources)) {
                    // Handle combined resources (with hyphen)
                    foreach ($combinedResources as $combinedResource) {
                        $resources = explode('-', $combinedResource);
                        $searchedAsteroids->whereHas('resources', function ($query) use ($resources) {
                            $query->whereIn('resource_type', $resources);
                        }, '=', count($resources));
                    }

                    // Remove the combined resources from the resourceFilter
                    $resourceFilter = array_diff($resourceFilter, $combinedResources);
                }

                // Handle remaining individual resources
                if (!empty($resourceFilter)) {
                    $searchedAsteroids->whereHas('resources', function ($query) use ($resourceFilter) {
                        $query->whereIn('resource_type', $resourceFilter);
                    });
                    Log::info('Individual resources: '. implode(', ', $resourceFilter));
                }
            }

            $searchedAsteroids = $searchedAsteroids->take(1000)->get();
        }

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

