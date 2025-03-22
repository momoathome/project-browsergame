<?php

namespace Orion\Modules\Actionqueue\Handlers;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Spacecraft\Services\SpacecraftProductionService;

class SpacecraftProductionHandler
{
    public function __construct(
        private readonly SpacecraftProductionService $spacecraftProductionService
    ) {}

    public function handle(ActionQueue $action): bool
    {
        return $this->spacecraftProductionService->completeProduction(
            $action->target_id, 
            $action->user_id,
            $action->details
        );
    }
}
