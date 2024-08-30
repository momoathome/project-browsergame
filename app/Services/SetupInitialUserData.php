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
        SpacecraftService $spacecraftService,
        UserResourceService $userResourceService,
        SetupInitialBuildings $buildingService,
        UserStationService $userStationService
    ) {
        $this->spacecraftService = $spacecraftService;
        $this->userResourceService = $userResourceService;
        $this->buildingService = $buildingService;
        $this->userStationService = $userStationService;
    }

    public function setupInitialData(User $user)
    {
        $userId = $user->id;
        $config = config('user_defaults');

        $this->spacecraftService->create($user, $config['spacecrafts']);
        $this->userResourceService->create($user, $config['userresources']);
        $this->buildingService->create($userId);
        $this->userStationService->create($user, $config['userstation']);
    }
}
