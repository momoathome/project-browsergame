<?php

namespace Orion\Modules\Asteroid\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Asteroid\Services\AsteroidSearch;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Asteroid\Services\AsteroidExplorer;
use Orion\Modules\Asteroid\Http\Requests\AsteroidExploreRequest;


class AsteroidController extends Controller
{
    public function __construct(
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly AsteroidSearch $asteroidSearch,
        private readonly QueueService $queueService,
        private readonly AuthManager $authManager
    ){
    }

    public function index()
    {
        return $this->renderAsteroidMap();
    }

    public function update(AsteroidExploreRequest $request)
    {
        $user = $this->authManager->user();
        $this->asteroidExplorer->exploreWithRequest($user, $request);

        return $this->renderAsteroidMap();
    }

    public function completeAsteroidMining($asteroidId, $userId, $details)
    {
        return $this->asteroidExplorer->completeAsteroidMining($asteroidId, $userId, $details);
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
}

