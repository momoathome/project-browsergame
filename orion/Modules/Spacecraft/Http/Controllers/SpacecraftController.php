<?php

namespace Orion\Modules\Spacecraft\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Spacecraft\Services\SpacecraftProductionService;

class SpacecraftController extends Controller
{
    public function __construct(
        private readonly SpacecraftService $spacecraftService,
        private readonly SpacecraftProductionService $spacecraftProductionService,
        private readonly AuthManager $authManager
    ) {
    }

    public function index()
    {
        $user = $this->authManager->user();
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithQueueInformation($user->id);

        return Inertia::render('Shipyard', [
            'spacecrafts' => $spacecrafts,
        ]);
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

        $result = $this->spacecraftProductionService->startSpacecraftProduction($user->id, $spacecraft, $quantity);

        if ($result['success']) {
            return redirect()->route('shipyard')->banner($result['message']);
        } else {
            return redirect()->route('shipyard')->dangerBanner($result['message']);
        }
    }

    public function unlock(Spacecraft $spacecraft)
    {
        $user = $this->authManager->user();
        
        $result = $this->spacecraftProductionService->unlockSpacecraft($user->id, $spacecraft);

        if ($result['success']) {
            return redirect()->route('shipyard')->banner($result['message']);
        } else {
            return redirect()->route('shipyard')->dangerBanner($result['message']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spacecraft $spacecraft)
    {
        //
    }
}
