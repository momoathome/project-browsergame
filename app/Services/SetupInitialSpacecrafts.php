<?php

namespace App\Services;

use App\Models\Spacecraft;
use App\Models\SpacecraftResourceCost;
use App\Models\Resource;

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
            'cargo' => $spacecraftConfig['cargo'],
            'unit_limit' => $spacecraftConfig['unitLimit'],
            'build_time' => $spacecraftConfig['buildTime'],
            'research_cost' => $spacecraftConfig['researchCost'],
            'unlocked' => $spacecraftConfig['unlocked'],
        ]);
    }
}
