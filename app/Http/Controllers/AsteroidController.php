<?php

namespace App\Http\Controllers;

use App\Models\Asteroid;
use App\Models\Station;
use App\Services\AsteroidExplorer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Spacecraft;


class AsteroidController extends Controller
{
    protected $asteroidExplorer;

    public function __construct(AsteroidExplorer $asteroidExplorer)
    {
        $this->asteroidExplorer = $asteroidExplorer;
    }

    public function index()
    {
        $asteroids = Asteroid::all();
        $stations = Station::all();
        $user = auth()->user();

        foreach ($asteroids as $asteroid) {
            $asteroid->resources = json_decode($asteroid->resources, true);
        }

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

    public function store(Request $request)
    {
        /* // Validierung der Eingabedaten
        $request->validate([
            'name' => 'required|string',
            'rarity' => 'required|string',
            'base' => 'required|numeric',
            'multiplier' => 'required|numeric',
            'value' => 'required|integer',
            'pool' => 'required|string',
            'resources' => 'required|array',
            'x' => 'required|integer',
            'y' => 'required|integer',
            'pixel_size' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['resources'] = json_encode($data['resources']);

        $asteroid = Asteroid::create($data);

        return response()->json($asteroid, 201); */
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

}

