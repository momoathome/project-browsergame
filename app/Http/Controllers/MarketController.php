<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserResource;


class MarketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Hole alle Marktdaten
        $market = Market::with('resource')
            ->orderBy('id', 'asc')
            ->get();

        // Übergibt die Marktdaten an die Inertia-Seite
        return Inertia::render('Market', [
            'market' => $market,
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
    public function show(market $market)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(market $market)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, market $market)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(market $market)
    {
        //
    }

    public function buy(Request $request)
    {

        $validated = $request->validate([
            'resource_id' => 'required|exists:markets,id',
            'amount' => 'required|integer|min:1',
        ]);

        $quantity = $validated['amount'];
        $user = Auth::user();

        $marketItem = Market::findOrFail($validated['resource_id']);

        if ($marketItem->stock < $quantity) {
            return redirect()->route('market')->dangerBanner(['not enough stock']);
        }

        DB::transaction(function () use ($marketItem, $user, $quantity) {
            $marketItem->stock -= $quantity;
            $marketItem->save();

            $userResource = UserResource::where('user_id', $user->id)
                ->where('resource_id', $marketItem->resource_id)
                ->first();

            if ($userResource) {
                $userResource->count += $quantity;
                $userResource->save();
            } else {
                UserResource::create([
                    'user_id' => $user->id,
                    'resource_id' => $marketItem->resource_id,
                    'count' => $quantity,
                ]);
            }
        });

        return redirect()->route('market')->banner('resource purchased successfully');
    }

    public function sell(Request $request)
    {
        $validated = $request->validate([
            'resource_id' => 'required|exists:markets,id',
            'amount' => 'required|integer|min:1',
        ]);

        $quantity = $validated['amount'];
        $user = Auth::user();

        $marketItem = Market::findOrFail($validated['resource_id']);

        $userResource = UserResource::where('user_id', $user->id)
        ->where('resource_id', $marketItem->resource_id)
        ->first();

        if (!$userResource || $userResource->count < $quantity) {
            return redirect()->route('market')->dangerBanner('Not enough resources');
        }

        DB::transaction(function () use ($marketItem, $quantity, $userResource) {
            // Reduziere die Menge des Benutzers
            $userResource->count -= $quantity;
            $userResource->save();
    
            // Füge die Menge zum Marktplatz hinzu
            $marketItem->stock += $quantity;
            $marketItem->save();
        });

        return redirect()->route('market')->banner('resource sold successfully');
    }
}
