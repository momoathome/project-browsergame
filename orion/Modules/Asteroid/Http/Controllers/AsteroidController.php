<?php

namespace Orion\Modules\Asteroid\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Log;
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
        $result = $this->asteroidService->StartAsteroidMining($user, request: $request);

        return response()->json([
            'message' => $result['message'],
            'asteroid' => $result['asteroid'] ?? null,
        ], 200);
    }

    public function search(Request $request)
    {
        $request->validate(['query' => 'nullable|string']);
        $query = $request->input('query');

        [$searchedAsteroids, $searchedStations] = $this->asteroidSearch->search($query);
        Log::info('AsteroidSearch: count=' . count($searchedAsteroids));
        
        return response()->json([
            'searched_asteroids' => $searchedAsteroids,
            'searched_stations' => $searchedStations,
        ], 200);
    }

    public function getAsteroidResources(Asteroid $asteroid)
    {
        $asteroid = $this->asteroidService->loadAsteroidWithResources($asteroid);
        
        return response()->json([
            'asteroid' => $asteroid,
        ], 200);
    }

    private function renderAsteroidMap()
    {
        $user = $this->authManager->user();
        $viewData = $this->asteroidService->getAsteroidMapData($user);

        return Inertia::render('AsteroidMap', $viewData);
    }
}
