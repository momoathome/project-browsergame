<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\UserResource;
use App\Models\BuildingResourceCost;

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

        $requiredResources = BuildingResourceCost::where('building_id', $building->id)->get();

        foreach ($requiredResources as $requiredResource) {
            $userResource = UserResource::where('user_id', Auth::id())
                ->where('resource_id', $requiredResource->resource_id)
                ->first();
    
            if (!$userResource || $userResource->count < $requiredResource->amount) {
                // Fehler-Banner setzen, bevor die Transaktion beginnt
                return redirect()->route('buildings')->dangerBanner('Not enough resources');
            }
        }

        DB::transaction(function () use ($building, $requiredResources) {
            foreach ($requiredResources as $requiredResource) {
                $userResource = UserResource::where('user_id', Auth::id())
                    ->where('resource_id', $requiredResource->resource_id)
                    ->first();
    
                $userResource->count -= $requiredResource->amount;
                $userResource->save();
            }
    
            $building->level += 1;
            $building->buildTime = $this->calculateNewBuildTime($building);
            $building->save();
    
            $this->updateResourceCosts($building);
        });
        
        return redirect()->route('buildings')->banner('Building upgraded successfully');
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
        return round($building->buildTime * 1.2);
    }

    private function updateResourceCosts(Building $building)
    {
        $costIncreaseFactor = 1.2; // 20% Erhöhung
        $building->load('resources');

        foreach ($building->resources as $resource) {
            $currentAmount = $resource->pivot->amount;
            $newAmount = round($currentAmount * $costIncreaseFactor);
            $building->resources()->updateExistingPivot($resource->id, ['amount' => $newAmount]);
        }
    }
}
