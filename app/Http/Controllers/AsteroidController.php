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

    public function search(Request $request)
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
    }
    

}

