<?php

namespace App\Http\Controllers;

use App\Models\Spacecraft;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserResource;
use App\Models\UserAttribute;
use Illuminate\Support\Facades\Log;

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
            ->orderBy('id', 'asc')
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
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $quantity = $validated['amount'];
        $user = auth()->user();

        Log::info('spacecraft amount: ' . $quantity);

        $totalCosts = $spacecraft->resources->mapWithKeys(function ($resource) use ($quantity) {
            return [$resource->id => $resource->pivot->amount * $quantity];
        });

        $unitLimit = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'unit_limit')
            ->first();

        if ($unitLimit && $unitLimit->attribute_value < $spacecraft->count + $quantity) {
            return redirect()->route('shipyard')->dangerBanner('Unit limit reached');
        }

        foreach ($totalCosts as $resourceId => $requiredResource) {
            $userResource = UserResource::where('user_id', $user->id)
                ->where('resource_id', $resourceId)
                ->first();

            if (!$userResource || $userResource->amount < $requiredResource) {
                return redirect()->route('shipyard')->dangerBanner('Not enough resources');
            }
        }

        DB::transaction(function () use ($spacecraft, $quantity, $user, $totalCosts) {
            foreach ($totalCosts as $resourceId => $requiredResource) {
                UserResource::where('user_id', $user->id)
                    ->where('resource_id', $resourceId)
                    ->decrement('amount', $requiredResource);
            }

            $spacecraft->count += $quantity;
            $spacecraft->save();


            UserAttribute::where('user_id', $user->id)
                ->where('attribute_name', 'total_units')
                ->increment('attribute_value', $quantity);
        });


        return redirect()->route('shipyard')->banner('Spacecraft produced successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spacecraft $spacecraft)
    {
        //
    }

}
