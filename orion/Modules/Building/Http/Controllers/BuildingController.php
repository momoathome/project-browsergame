<?php

namespace Orion\Modules\Building\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Building\Services\BuildingUpgradeService;

class BuildingController extends Controller
{
    public function __construct(
        private readonly BuildingService $buildingService,
        private readonly BuildingUpgradeService $buildingUpgradeService,
        private readonly AuthManager $authManager
    ) {
    }

    public function index()
    {
        $user = $this->authManager->user();
        $buildings = $this->buildingService->formatBuildingsForDisplay($user->id);

        return Inertia::render('Buildings', [
            'buildings' => $buildings,
        ]);
    }

    public function update(Building $building)
    {
        $user = $this->authManager->user();
        
        $result = $this->buildingUpgradeService->startBuildingUpgrade($user->id, $building);
        if ($result['success']) {
            return redirect()->route('buildings')->banner($result['message']);
        } else {
            return redirect()->route('buildings')->dangerBanner($result['message']);
        }
    }
}
