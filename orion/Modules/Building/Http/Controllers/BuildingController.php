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
        $buildings = $this->buildingService->getAllBuildingsByUserIdWithQueueInformation($user->id);

        return Inertia::render('Buildings', [
            'buildings' => $buildings,
        ]);
    }

    public function update(Building $building)
    {
        $user = $this->authManager->user();
        
        try {
            $this->buildingUpgradeService->startBuildingUpgrade($user->id, $building);
            return redirect()->route('buildings')->banner('Building upgrade started');
        } catch (\Exception $e) {
            return redirect()->route('buildings')->dangerBanner($e->getMessage());
        }
    }

}
