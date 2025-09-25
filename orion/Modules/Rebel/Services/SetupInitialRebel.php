<?php

namespace Orion\Modules\Rebel\Services;

use Orion\Modules\Rebel\Models\Rebel;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Asteroid\Services\UniverseService;

class SetupInitialRebel
{
    private $universeService;

    public function __construct(UniverseService $universeService)
    {
        $this->universeService = $universeService;
    }

    public function create(string $leaderName, string $faction)
    {
        $coordinate = $this->universeService->findValidRebelCoordinates($faction);

        if ($coordinate === null) {
            throw new \Exception('No available coordinates for rebel placement.');
        }

        return Rebel::create([
            'name' => $leaderName,
            'faction' => $faction,
            'x' => $coordinate['x'],
            'y' => $coordinate['y'],
        ]);
    }

}
