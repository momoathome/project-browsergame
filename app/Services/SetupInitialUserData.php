<?php

namespace App\Services;

use App\Models\User;

class SetupInitialUserData
{
    protected $spacecraftService;
    protected $userResourceService;
    protected $userAttributeService;
    protected $buildingService;
    protected $userStationService;

    public function __construct(
        SetupInitialSpacecrafts $spacecraftService,
        SetupInitialUserResources $userResourceService,
        SetupInitialUserAttribute $userAttributeService,
        SetupInitialBuildings $buildingService,
        SetupInitialStation $userStationService
    ) {
        $this->spacecraftService = $spacecraftService;
        $this->userResourceService = $userResourceService;
        $this->userAttributeService = $userAttributeService;
        $this->buildingService = $buildingService;
        $this->userStationService = $userStationService;
    }

    public function setupInitialData(User $user)
    {
        $userId = $user->id;
        $userName = $user->name;

        $this->spacecraftService->create($userId);
        $this->userResourceService->create($userId);
        $this->userAttributeService->create($userId);
        $this->buildingService->create($userId);
        $this->userStationService->create($userId, $userName);
    }
}
