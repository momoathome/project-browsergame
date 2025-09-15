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

    public function reset(int $userId)
    {
        $spacecraftIds = Spacecraft::where('user_id', $userId)->pluck('id');

        SpacecraftResourceCost::whereIn('spacecraft_id', $spacecraftIds)->delete();

        Spacecraft::where('user_id', $userId)->delete();
    }

    private function createSpacecraft(int $userId, array $spacecraftConfig)
    {
        return Spacecraft::create([
            'user_id' => $userId,
            'details_id' => $spacecraftConfig['details_id'],
            'attack' => $spacecraftConfig['attack'],
            'defense' => $spacecraftConfig['defense'],
            'combat' => $spacecraftConfig['combat'],
            'cargo' => $spacecraftConfig['cargo'],
            'speed' => $spacecraftConfig['speed'],
            'operation_speed' => $spacecraftConfig['operation_speed'],
            'count' => $spacecraftConfig['count'],
            'locked_count' => $spacecraftConfig['locked_count'],
            'build_time' => $spacecraftConfig['build_time'],
            'crew_limit' => $spacecraftConfig['crew_limit'],
            'research_cost' => $spacecraftConfig['research_cost'],
            'unlocked' => $spacecraftConfig['unlocked'],
        ]);
    }
}
