<?php

namespace Orion\Modules\Station\Services;

use Orion\Modules\Station\Models\Station;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Asteroid\Services\UniverseService;

class SetupInitialStation
{
    private $universeService;

    public function __construct(UniverseService $universeService)
    {
        $this->universeService = $universeService;
    }

    public function create(int $userId, string $userName)
    {
        // Station in einer reservierten Region platzieren

        $coordinate = $this->universeService->assignStationRegion();
        if ($coordinate === null) {
            throw new \Exception('No available region for station placement.');
        }

        $station = Station::create([
            'user_id' => $userId,
            'name' => $userName,
            'x' => $coordinate['x'],
            'y' => $coordinate['y'],
        ]);

        // Cache aktualisieren
        Cache::forget('universe:stations');

        return $station;
    }
}
