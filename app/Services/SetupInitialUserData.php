<?php

namespace App\Services;

use App\Models\User;

class SetupInitialUserData
{
    protected $spacecraftService;
    protected $userResourceService;
    protected $buildingService;
    protected $userStationService;

    public function __construct(
        SetupInitialSpacecrafts $spacecraftService,
        SetupInitialUserResources $userResourceService,
        SetupInitialBuildings $buildingService,
        SetupInitialStation $userStationService
    ) {
        $this->spacecraftService = $spacecraftService;
        $this->userResourceService = $userResourceService;
        $this->buildingService = $buildingService;
        $this->userStationService = $userStationService;
    }

    public function setupInitialData(User $user)
    {
        $userId = $user->id;

        $this->spacecraftService->create($userId);
        $this->userResourceService->create($userId);
        $this->buildingService->create($userId);
        $this->userStationService->create($userId, $user->name);
    }
}
