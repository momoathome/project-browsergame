<?php

namespace Orion\Modules\Actionqueue\Handlers;

use Orion\Modules\Combat\Services\CombatOrchestrationService;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Combat\Dto\CombatResult;

class CombatHandler
{
    public function __construct(
        private readonly CombatOrchestrationService $combatService
    )
    {}
    public function handle(ActionQueue $action): bool
    {
        $result = $this->combatService->completeCombat(
            $action->user_id,
            $action->target_id,
            $action->details
        );

        return $result instanceof CombatResult ? $result->wasSuccessful() : (bool)$result;
    }
}
