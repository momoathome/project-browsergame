<?php

namespace Orion\Modules\User\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class OverviewController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly BuildingService $buildingService,
        private readonly SpacecraftService $spacecraftService
    ) {
    }

    public function index()
    {
        $user = $this->authManager->user();

        $buildings = $this->buildingService->formatBuildingsForDisplay($user->id);
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($user->id);

        return Inertia::render('Overview', [
            'buildings' => $buildings,
            'spacecrafts' => $spacecrafts,
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
