<?php

namespace Orion\Modules\Actionqueue\Handlers;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Building\Services\BuildingUpgradeService;

class BuildingUpgradeHandler
{
    public function __construct(
        private readonly BuildingUpgradeService $buildingUpgradeService
    ) {}

    public function handle(ActionQueue $action): bool
    {
        return $this->buildingUpgradeService->completeUpgrade(
            $action->target_id, 
            $action->user_id
        );
    }
}
