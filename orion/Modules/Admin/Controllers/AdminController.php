<?php

namespace Orion\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Building\Http\Controllers\BuildingController;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
/*         $resources = Asteroid::with('resources')
            ->get()
            ->pluck('resources')
            ->flatten()
            ->groupBy('resource_type')
            ->map(function ($resources) {
                return [$resources->sum('amount')];
            }); */

        // get all users with their stations and spacecrafts
        $users = User::all();

        return Inertia::render('Admin/Dashboard', [
            // 'universeResources' => $resources,
            'users' => $users,
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
    public function show(string $id)
    {
        // show specific user with their stations and spacecrafts
        $user = User::with('stations')
            ->find($id);

        $buildings = $user->buildings()->with('details')->orderBy('id', 'asc')->get();
        $spacecrafts = $user->spacecrafts()->with('details')->orderBy('id', 'asc')->get();
        $ressources = $user->resources()->get();

        return Inertia::render('Admin/UserDetail', [
            'user' => $user,
            'buildings' => $buildings,
            'spacecrafts' => $spacecrafts,
            'ressources' => $ressources,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $station = Station::find($id);
        $station->update([
            'x' => $request->x,
            'y' => $request->y,
        ]);
    }

    public function updateBuilding(Request $request, string $id)
    {
        $request->validate([
            'building_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);
    
        $buildingId = $request->building_id;
        $userId = $request->user_id;
        
        $buildingController = app(BuildingController::class);
        $result = $buildingController->completeUpgrade($buildingId, $userId);
        
        if ($result) {
            return redirect()->back()->with('message', 'Gebäude wurde erfolgreich aufgewertet');
        } else {
            return redirect()->back()->with('error', 'Fehler beim Aufwerten des Gebäudes');
        }
    }

    public function updateSpacecraft(Request $request)
    {
        $request->validate([
            'count' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $spacecraft = Spacecraft::find($request->id);
        $spacecraft->update([
            'count' => $request->count,
            'user_id' => $request->user_id,
        ]);

        return redirect()->back()->with('message', 'Raumschiff erfolgreich aktualisiert');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
