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

    public function create(array $data)
    {
        $coordinate = $this->universeService->findValidRebelCoordinates($data['faction']);

        if ($coordinate === null) {
            throw new \Exception('No available coordinates for rebel placement.');
        }

        $data['x'] = $coordinate['x'];
        $data['y'] = $coordinate['y'];

        return Rebel::create($data);
    }


}
