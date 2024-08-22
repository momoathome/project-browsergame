<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuildingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Hole den aktuell angemeldeten Benutzer
        $user = auth()->user();

        // Hole die Gebäude-Daten für den aktuell angemeldeten Benutzer
        $buildings = Building::with('schema')
            ->where('user_id', $user->id)
            ->get();

        // Übergibt die Daten an die Inertia-Seite
        return Inertia::render('Buildings', [
            'buildings' => $buildings,
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
    public function show(building $building)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(building $building)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, building $building)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(building $building)
    {
        //
    }
}
