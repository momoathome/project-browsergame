<?php

namespace Orion\Modules\Market\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Market\Services\MarketService;


class MarketController extends Controller
{
    public function __construct(
        private readonly MarketService $marketService,
        private readonly AuthManager $authManager
    ) {
    }

    public function index()
    {
        $market = $this->marketService->getMarketData();

        return Inertia::render('Market', [
            'market' => $market,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->authManager->user()->hasRole('admin')) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'cost' => 'required|integer|min:1',
            'stock' => 'required|integer|min:1',
        ]);

        $this->marketService->updateResourceAmount($id, $validated['stock'], $validated['cost']);
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
