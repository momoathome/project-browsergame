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
        
        // Log für Debugging
        Log::info('Building upgrade result:', [
            'action_id' => $action->id,
            'user_id' => $action->user_id,
            'target_id' => $action->target_id,
            'result' => $result
        ]);
        
        // Überprüfen Sie den success-Schlüssel im zurückgegebenen Array
        return isset($result['success']) && $result['success'] === true;
    }
}
