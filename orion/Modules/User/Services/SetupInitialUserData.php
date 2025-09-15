<?php

namespace Orion\Modules\User\Services;

use App\Models\User;
use Orion\Modules\Spacecraft\Services\SetupInitialSpacecrafts;
use Orion\Modules\User\Services\SetupInitialUserResources;
use Orion\Modules\User\Services\SetupInitialUserAttribute;
use Orion\Modules\Building\Services\SetupInitialBuildings;
use Orion\Modules\Station\Services\SetupInitialStation;

class SetupInitialUserData
{
    public function __construct(
        private readonly SetupInitialSpacecrafts $spacecraftService,
        private readonly SetupInitialUserResources $userResourceService,
        private readonly SetupInitialUserAttribute $userAttributeService,
        private readonly SetupInitialBuildings $buildingService,
        private readonly SetupInitialStation $userStationService
    ) {
    }

    public function setupInitialData(User $user, bool $isNewUser = true)
    {
        $userId = $user->id;
        $userName = $user->name;

        $this->spacecraftService->create($userId);
        $this->userResourceService->create($userId);
        $this->userAttributeService->create($userId);
        $this->buildingService->create($userId);
        if ($isNewUser) {
            $station = $this->userStationService->create($userId, $userName);

            app(\Orion\Modules\Asteroid\Services\AsteroidGenerator::class)
                ->generateStrategicLowValueAsteroids(
                    config('game.core.strategic_asteroid_count'),
                    config('game.core.strategic_asteroid_outer_radius'),
                    [$station]
                );
        }
    }
}
