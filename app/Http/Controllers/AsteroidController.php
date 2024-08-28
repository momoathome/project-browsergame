<?php

namespace App\Http\Controllers;

use App\Models\Asteroid;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Spacecraft;


class AsteroidController extends Controller
{
    public function index()
    {
        $asteroids = Asteroid::all();
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
        ]);
    }

    public function store(Request $request)
    {
        // Validierung der Eingabedaten
        $request->validate([
            'name' => 'required|string',
            'rarity' => 'required|string',
            'base' => 'required|numeric',
            'multiplier' => 'required|numeric',
            'value' => 'required|integer',
            'resources' => 'required|array',
            'x' => 'required|integer',
            'y' => 'required|integer',
            'pixel_size' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['resources'] = json_encode($data['resources']);

        $asteroid = Asteroid::create($data);

        return response()->json($asteroid, 201);
    }

    public function update(Request $request, Asteroid $asteroid)
    {
/*         $data = $request->all();
        $data['resources'] = json_encode($data['resources']);

        $asteroid->update($data); */

        return response()->json($asteroid, 201);
    }
}

