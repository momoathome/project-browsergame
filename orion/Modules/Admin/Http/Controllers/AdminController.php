<?php

namespace Orion\Modules\Admin\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Controllers\Controller;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Building\Services\BuildingUpgradeService;

class AdminController extends Controller
{

    public function __construct(
        private readonly BuildingUpgradeService $buildingUpgradeService,
        private readonly BuildingService $buildingService,
        private readonly UserService $userService,
        private readonly StationService $stationService,
        private readonly SpacecraftService $spacecraftService,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService
    ) {
    }

    public function index()
    {
        // get all users with their stations and spacecrafts
        $users = $this->userService->findAll();

        return Inertia::render('Admin/Dashboard', [
            'users' => $users,
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
    public function show(string $id)
    {
        // show specific user with their stations and spacecrafts
        $user = User::with('stations')
            ->find($id);

        $buildings = $this->buildingService->formatBuildingsForDisplay($id);
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($id);
        $resources = $this->userResourceService->getAllUserResourcesByUserId($id);
        $attributes = $this->userAttributeService->getAllUserAttributesByUserId($id);

        return Inertia::render('Admin/UserDetail', [
            'user' => $user,
            'buildings' => $buildings,
            'spacecrafts' => $spacecrafts,
            'resources' => $resources,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $station = $this->stationService->findStationById($id);
        $station->update([
            'x' => $request->x,
            'y' => $request->y,
        ]);
    }

    public function updateBuilding(Request $request, string $id)
    {
        $request->validate([
            'building_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $buildingId = $request->building_id;
        $userId = $request->user_id;

        $result = $this->buildingUpgradeService->completeUpgrade($buildingId, $userId);

        if ($result) {
            return redirect()->back()->with('message', 'Gebäude wurde erfolgreich aufgewertet');
        } else {
            return redirect()->back()->with('error', 'Fehler beim Aufwerten des Gebäudes');
        }
    }

    public function updateSpacecraft(Request $request)
    {
        $request->validate([
            'count' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $spacecraft = $this->spacecraftService->findSpacecraftById($request->spacecraft_id);
        $spacecraft->update([
            'count' => $request->count,
            'user_id' => $request->user_id,
        ]);

        return redirect()->back()->with('message', 'Raumschiff erfolgreich aktualisiert');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
