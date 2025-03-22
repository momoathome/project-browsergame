<?php

namespace Orion\Modules\Actionqueue\Handlers;

use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Asteroid\Services\AsteroidMiningService;

class AsteroidMiningHandler
{
    public function __construct(
        private readonly AsteroidMiningService $asteroidMiningService
    ) {}

    public function handle(ActionQueue $action): bool
    {
        return $this->asteroidMiningService->completeMining(
            $action->target_id, 
            $action->user_id
        );
    }
}
