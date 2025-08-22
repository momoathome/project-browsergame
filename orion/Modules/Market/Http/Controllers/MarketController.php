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
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'cost' => 'required|integer|min:1',
            'stock' => 'required|integer|min:1',
        ]);

        $this->marketService->updateResourceAmount($id, $validated['stock'], $validated['cost']);
    }

    public function buy(Request $request, Market $marketRes)
    {
        $marketRes->load('resource');
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);
        $user = $this->authManager->user();
        if (!$user instanceof \App\Models\User) {
            throw new \LogicException('Authenticated user is not of type App\Models\User');
        }

        $this->marketService->buyResource($user, $marketRes, $validated['amount']);
    }

    public function sell(Request $request, Market $marketRes)
    {
        $marketRes->load('resource');
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);
        $user = $this->authManager->user();
        if (!$user instanceof \App\Models\User) {
            throw new \LogicException('Authenticated user is not of type App\Models\User');
        }

        $this->marketService->sellResource($user, $marketRes, $validated['amount']);
    }
}
