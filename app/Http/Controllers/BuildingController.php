<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserResource;
use App\Models\UserAttribute;
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

        $user = auth()->user();
        $requiredResources = BuildingResourceCost::where('building_id', $building->id)->get();

        foreach ($requiredResources as $requiredResource) {
            $userResource = UserResource::where('user_id', $user->id)
                ->where('resource_id', $requiredResource->resource_id)
                ->first();

            if (!$userResource || $userResource->amount < $requiredResource->amount) {
                // Fehler-Banner setzen, bevor die Transaktion beginnt
                return redirect()->route('buildings')->dangerBanner('Not enough resources');
            }
        }

        DB::transaction(function () use ($user, $building, $requiredResources) {
            foreach ($requiredResources as $requiredResource) {
                $userResource = UserResource::where('user_id', $user->id)
                    ->where('resource_id', $requiredResource->resource_id)
                    ->first();

                $userResource->amount -= $requiredResource->amount;
                $userResource->save();
            }

            $building->level += 1;
            $building->build_time = $this->calculateNewBuildTime($building);
            $building->save();

            $buildingEffects = [
                'Hangar' => ['unit_limit' => 10],
                'Warehouse' => ['storage' => 1.3, 'multiply' => true],
                'Laboratory' => ['research_points' => 2],
                'Scanner' => ['scan_range' => 5000],
            ];

            if (isset($buildingEffects[$building->details->name])) {
                foreach ($buildingEffects[$building->details->name] as $attributeName => $value) {
                    $this->updateUserAttribute($user->id, $attributeName, $value, $buildingEffects[$building->details->name]['multiply'] ?? false);
                }
            }

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

    private function updateUserAttribute($userId, $attributeName, $value, $multiply = false)
    {
        $userAttribute = UserAttribute::where('user_id', $userId)
            ->where('attribute_name', $attributeName)
            ->first();

        if ($userAttribute) {
            if ($multiply) {
                $userAttribute->attribute_value = round($userAttribute->attribute_value * $value);
            } else {
                $userAttribute->attribute_value += $value;
            }
            $userAttribute->save();
        }
    }

    private function calculateNewBuildTime(Building $building)
    {
        return round($building->build_time * 1.2);
    }

    private function updateResourceCosts(Building $building)
    {
        $costIncreaseFactor = 1.35; // 35% Erhöhung
        $building->load('resources');

        foreach ($building->resources as $resource) {
            $currentAmount = $resource->pivot->amount;
            $newAmount = round($currentAmount * $costIncreaseFactor);
            $building->resources()->updateExistingPivot($resource->id, ['amount' => $newAmount]);
        }
    }
}
