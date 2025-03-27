<?php

namespace Orion\Modules\Spacecraft\Services;

use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Spacecraft\Models\SpacecraftResourceCost;
use Orion\Modules\Resource\Models\Resource;

class SetupInitialSpacecrafts
{
    public function create(int $userId)
    {
        $spacecraftsConfig = config('game.spacecrafts.spacecrafts');
        $resources = Resource::pluck('id', 'name')->toArray();

        foreach ($spacecraftsConfig as $spacecraftConfig) {
            $spacecraft = $this->createSpacecraft($userId, $spacecraftConfig);

            foreach ($spacecraftConfig['costs'] as $cost) {
                SpacecraftResourceCost::create([
                    'spacecraft_id' => $spacecraft->id,
                    'resource_id' => $resources[$cost['resource_name']],
                    'amount' => $cost['amount'],
                ]);
            }
        }
    }

    private function createSpacecraft(int $userId, array $spacecraftConfig)
    {
        return Spacecraft::create([
            'user_id' => $userId,
            'details_id' => $spacecraftConfig['details_id'],
            'combat' => $spacecraftConfig['combat'],
            'count' => $spacecraftConfig['count'],
            'locked_count' => $spacecraftConfig['lockedCount'],
            'cargo' => $spacecraftConfig['cargo'],
            'speed' => $spacecraftConfig['speed'],
            'operation_speed' => $spacecraftConfig['operation_speed'],
            'crew_limit' => $spacecraftConfig['crewLimit'],
            'build_time' => $spacecraftConfig['buildTime'],
            'research_cost' => $spacecraftConfig['researchCost'],
            'unlocked' => $spacecraftConfig['unlocked'],
        ]);
    }
}
