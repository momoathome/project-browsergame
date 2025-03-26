<?php

namespace Orion\Modules\Asteroid\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Services\AsteroidSearch;
use Orion\Modules\Asteroid\Services\AsteroidService;
use Orion\Modules\Asteroid\Http\Requests\AsteroidExploreRequest;

class AsteroidController extends Controller
{
    public function __construct(
        private readonly AsteroidService $asteroidService,
        private readonly AsteroidSearch $asteroidSearch,
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
        $this->asteroidService->asteroidMining($user, $request);

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

        return $this->renderAsteroidMap($searchedAsteroids, $searchedStations, null, $query);
    }

    public function getAsteroidResources(Asteroid $asteroid)
    {
        $asteroid = $this->asteroidService->loadAsteroidWithResources($asteroid);
        
        return $this->renderAsteroidMap([], [], $asteroid);
    }

    private function renderAsteroidMap($searchedAsteroids = [], $searchedStations = [], $selectedAsteroid = null, $query = null)
    {
        $viewData = $this->asteroidService->getAsteroidMapData(
            auth()->user(),
            $searchedAsteroids,
            $searchedStations,
            $selectedAsteroid
        );

        if ($query) {
            $viewData['search_query'] = $query;
        }

        return Inertia::render('AsteroidMap', $viewData);
    }

/*     private function renderAsteroidMap($searchedAsteroids = [], $searchedStations = [], $selectedAsteroid = null)
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
    } */
}
