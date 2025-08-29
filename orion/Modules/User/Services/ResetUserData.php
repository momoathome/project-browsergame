<?php

namespace Orion\Modules\User\Services;

use App\Models\User;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Spacecraft\Services\SetupInitialSpacecrafts;
use Orion\Modules\User\Services\SetupInitialUserResources;
use Orion\Modules\User\Services\SetupInitialUserAttribute;
use Orion\Modules\Building\Services\SetupInitialBuildings;
use Orion\Modules\Station\Services\SetupInitialStation;
use Orion\Modules\User\Services\SetupInitialUserData;

class ResetUserData
{
    public function __construct(
        private readonly SetupInitialSpacecrafts $spacecraftService,
        private readonly SetupInitialUserResources $userResourceService,
        private readonly SetupInitialUserAttribute $userAttributeService,
        private readonly SetupInitialBuildings $buildingService,
        private readonly SetupInitialStation $userStationService,
        private readonly SetupInitialUserData $userDataService,

    ) {
    }

    public function resetUserData(User $user)
    {
        $userId = $user->id;

        $this->spacecraftService->reset($userId);
        $this->userResourceService->reset($userId);
        $this->buildingService->reset($userId);
        $this->userAttributeService->reset($userId);
        ActionQueue::where('user_id', $userId)->delete();
        $this->userDataService->setupInitialData($user, false);
    }
}
