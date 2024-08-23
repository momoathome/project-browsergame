<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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
        $buildings = Building::with('details', 'resources')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
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
        DB::transaction(function () use ($building) {
        $building->level += 1;
        $building->buildTime = $this->calculateNewBuildTime($building);
        $building->cost = $this->calculateNewCost($building);
        $building->save();
        });

        return redirect()->back()->with('success', 'Building upgraded successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(building $building)
    {
        //
    }

    private function calculateNewBuildTime(Building $building)
    {
        // Beispiel für eine Berechnung, kann je nach Logik angepasst werden
        return round($building->buildTime * 1.5);
    }

    private function calculateNewCost(Building $building)
    {
        // Beispiel für eine Berechnung, kann je nach Logik angepasst werden
        return round($building->cost * 1.3);
    }
}
