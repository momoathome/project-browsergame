<?php

namespace App\Http\Controllers;

use App\Models\Asteroid;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AsteroidController extends Controller
{
    public function index()
    {
        $asteroids = Asteroid::all();

        foreach ($asteroids as $asteroid) {
            $asteroid->resources = json_decode($asteroid->resources, true);
        }
        
        return Inertia::render('AsteroidMap', [
            'asteroids' => $asteroids,
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
}

