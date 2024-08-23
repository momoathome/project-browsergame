<?php

namespace App\Http\Controllers;

use App\Models\Spacecraft;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpacecraftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Hole den aktuell angemeldeten Benutzer
        $user = auth()->user();

        // Hole die spacecraft-Daten für den aktuell angemeldeten Benutzer
        $spacecrafts = Spacecraft::with('details', 'resources')
            ->where('user_id', $user->id)
            ->get();

        // Übergibt die Daten an die Inertia-Seite
        return Inertia::render('Shipyard', [
            'spacecrafts' => $spacecrafts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Spacecraft $spacecraft)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Spacecraft $spacecraft)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Spacecraft $spacecraft)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spacecraft $spacecraft)
    {
        //
    }
}
