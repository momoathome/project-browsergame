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

    public function create(int $rebelId, string $rebelName)
    {
        // Koordinaten für Rebellen finden
        $coordinate = $this->universeService->findValidRebelCoordinates();
        if ($coordinate === null) {
            throw new \Exception('No available coordinates for rebel placement.');
        }

        $rebel = Rebel::create([
            'name' => $rebelName,
            'faction' => 'Rebels',
            'x' => $coordinate['x'],
            'y' => $coordinate['y'],
        ]);

        return $rebel;
    }
}
