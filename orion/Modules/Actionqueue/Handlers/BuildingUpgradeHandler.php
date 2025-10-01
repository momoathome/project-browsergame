<?php

namespace Orion\Modules\Actionqueue\Handlers;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Building\Services\BuildingUpgradeService;
use Illuminate\Support\Facades\Log;

class BuildingUpgradeHandler
{
    public function __construct(
        private readonly BuildingUpgradeService $buildingUpgradeService
    ) {}

    public function handle(ActionQueue $action): bool
    {
        $result = $this->buildingUpgradeService->completeUpgrade(
            $action->target_id, 
            $action->user_id
        );
        
        // gebe status zurück
        return isset($result['success']) && $result['success'] === true;
    }
}
