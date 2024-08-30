<?php

namespace App\Services;

use App\Models\Building;
use App\Models\BuildingResourceCost;
use App\Models\Resource;

class SetupInitialBuildings
{
    public function create(int $userId)
    {
        $buildingsConfig = config('buildings.buildings');
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
            'level' => $buildingConfig['level'],
            'details_id' => $buildingConfig['details_id'],
            'effect_value' => $buildingConfig['effect_value'],
            'buildTime' => $buildingConfig['build_time'],
        ]);
    }
}
