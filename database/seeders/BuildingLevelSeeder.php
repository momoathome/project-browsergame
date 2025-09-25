<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Building\Models\BuildingLevel;

class BuildingLevelSeeder extends Seeder
{
    public function run(): void
    {
        $buildingLevelEffects = config('game.building_progression.building_level_effects');

        foreach ($buildingLevelEffects as $building => $levels) {
            foreach ($levels as $level => $effects) {
                BuildingLevel::updateOrCreate(
                    [
                        'building_key' => $building,
                        'level' => $level,
                    ],
                    [
                        'effects' => $effects,
                    ]
                );
            }
        }
    }
}
