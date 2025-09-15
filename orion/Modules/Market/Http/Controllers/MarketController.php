<?php

namespace Orion\Modules\Market\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Market\Models\Market;
use Orion\Modules\Market\Services\MarketService;


class MarketController extends Controller
{
    public function __construct(
        private readonly MarketService $marketService,
        private readonly AuthManager $authManager
    ) {
    }

    public function index(Request $request)
    {
        $market = $this->marketService->getMarketData();
        $categoryValues = config('game.market.market_category_values');

        return Inertia::render('Market', [
            'market' => $market,
            'categoryValues' => $categoryValues,
            'prefill_resource_ids' => $request->input('resource_ids'),
            'prefill_amounts' => $request->input('amounts'),
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->authManager->user()->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'cost' => 'required|integer|min:1',
            'stock' => 'required|integer|min:1',
        ]);

        $this->marketService->updateResourceAmount($id, $validated['stock'], $validated['cost']);
    }

    public function trade(Request $request)
    {
        $validated = $request->validate([
            'give_resource_id' => 'required|integer|different:receive_resource_id',
            'receive_resource_id' => 'required|integer',
            'give_amount' => 'required|integer|min:1',
        ]);

        $user = $this->authManager->user();
        if (!$user instanceof \App\Models\User) {
            throw new \LogicException('Authenticated user is not of type App\Models\User');
        }

        $giveRes = Market::with('resource')->findOrFail($validated['give_resource_id']);
        $receiveRes = Market::with('resource')->findOrFail($validated['receive_resource_id']);

        $result = $this->marketService->tradeResources($user, $giveRes, $validated['give_amount'], $receiveRes);

        if ($result['success']) {
            return redirect()->route('market')->banner($result['message']);
        }

        return redirect()->route('market')->dangerBanner($result['message']);
    }

}
