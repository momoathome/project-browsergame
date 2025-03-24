<?php

namespace Orion\Modules\Market\Http\Controllers;

use App\Http\Controllers\Controller;
use Orion\Modules\Market\Services\MarketService;
use Illuminate\Http\Request;
use Inertia\Inertia;


class MarketController extends Controller
{
    public function __construct(
        private readonly MarketService $marketService
    ) {
    }

    public function index()
    {
        $market = $this->marketService->getMarketData();

        return Inertia::render('Market', [
            'market' => $market,
        ]);
    }

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
