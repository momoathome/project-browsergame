<?php

namespace Orion\Modules\Actionqueue\Handlers;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Asteroid\Services\AsteroidService;
use Orion\Modules\Asteroid\Dto\ExplorationResult;

class AsteroidMiningHandler
{
    public function __construct(
        private readonly AsteroidService $asteroidService
    ) {}

    public function handle(ActionQueue $action): ExplorationResult|bool
    {
        $result = $this->asteroidService->completeAsteroidMining(
            $action->target_id, 
            $action->user_id,
            $action->details,
            $action->id
        );


        // gebe das exploration result zurück
        return $result;
    }
}
