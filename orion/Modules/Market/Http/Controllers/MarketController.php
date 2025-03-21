<?php

namespace Orion\Modules\Market\Http\Controllers;

use App\Http\Controllers\Controller;
use Orion\Modules\Market\Services\MarketService;
use Orion\Modules\market\Models\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserResource;
use App\Models\Resource;


class MarketController extends Controller
{
    public function __construct(
        private readonly MarketService $marketService
    ) {
    }

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
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }

    public function buy(Request $request)
    {
        $validated = $request->validate([
            'resource_id' => 'required|exists:markets,id',
            'amount' => 'required|integer|min:1',
        ]);

        $this->marketService->buyResource($validated['resource_id'], $validated['amount']);
    }

    public function sell(Request $request)
    {
        $validated = $request->validate([
            'resource_id' => 'required|exists:markets,id',
            'amount' => 'required|integer|min:1',
        ]);

        $this->marketService->sellResource($validated['resource_id'], $validated['amount']);
    }
}
