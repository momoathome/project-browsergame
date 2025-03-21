<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Models\BuildingResourceCost;
use Orion\Modules\Resource\Models\Resource;

class SetupInitialBuildings
{
    public function create(int $userId)
    {
        $buildingsConfig = config('game.buildings.buildings');
        $resources = Resource::pluck('id', 'name')->toArray();

        foreach ($buildingsConfig as $buildingConfig) {
            $building = $this->createBuilding($userId, $buildingConfig);

            foreach ($buildingConfig['costs'] as $cost) {
                BuildingResourceCost::create([
                    'building_id' => $building->id,
                    'resource_id' => $resources[$cost['resource_name']],
                    'amount' => $cost['amount'],
                ]);
            }
        }
    }

    private function createBuilding(int $userId, array $buildingConfig)
    {
        return Building::create([
            'user_id' => $userId,
            'details_id' => $buildingConfig['details_id'],
            'level' => $buildingConfig['level'],
            'effect_value' => $buildingConfig['effect_value'],
            'build_time' => $buildingConfig['build_time'],
        ]);
    }
}
