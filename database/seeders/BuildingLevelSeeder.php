<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Building\Models\BuildingLevel;

class BuildingLevelSeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            // 🏛 Core / Command Center
            ['Core', 1, ['building_slots' => 1]],
            ['Core', 3, ['building_slots' => 2]],
            ['Core', 5, ['building_slots' => 3]],
            ['Core', 10, ['building_slots' => 4]],
            ['Core', 15, ['building_slots' => 5]],

            // 🛠 Shipyard
            ['Shipyard', 1, ['production_slots' => 1, 'unlock' => ['Mole']]],
            ['Shipyard', 2, ['unlock' => ['Merlin']]],
            ['Shipyard', 3, ['production_slots' => 2, 'unlock' => ['Comet']]],
            ['Shipyard', 4, ['unlock' => ['Nomad']]],
            ['Shipyard', 5, ['production_slots' => 3,'unlock' => ['javelin']]],
            ['Shipyard', 6, ['unlock' => ['Sentinel']]],
            ['Shipyard', 7, ['unlock' => ['Titan']]],
            ['Shipyard', 8, ['production_slots' => 4, 'unlock' => ['Hercules']]],
            ['Shipyard', 9, ['unlock' => ['Probe']]],
            ['Shipyard', 10, ['production_slots' => 5, 'unlock' => ['Ares']]],
            ['Shipyard', 12, ['unlock' => ['Nova']]],
            ['Shipyard', 15, ['production_slots' => 6, 'unlock' => ['Horus']]],
            ['Shipyard', 20, ['production_slots' => 7, 'unlock' => ['Reaper']]],

            // 🛩 Hangar
            ['Hangar', 1, ['dock_slots' => 2]],
            ['Hangar', 2, ['dock_slots' => 5]],
            ['Hangar', 3, ['dock_slots' => 10]],
            ['Hangar', 4, ['dock_slots' => 15]],
            ['Hangar', 5, ['dock_slots' => 20, 'unlock' => ['auto_mining']]],
            ['Hangar', 6, ['dock_slots' => 30]],
            ['Hangar', 7, ['dock_slots' => 40]],
            ['Hangar', 8, ['dock_slots' => 50]],
            ['Hangar', 9, ['dock_slots' => 60]],
            ['Hangar', 10, ['dock_slots' => 75]],
            ['Hangar', 12, ['dock_slots' => 90]],
            ['Hangar', 15, ['dock_slots' => 120]],
            ['Hangar', 20, ['dock_slots' => 200]],

            // 🧪 Laboratory
            ['Laboratory', 5, ['unlock' => ['science_branch_industry']]],
            ['Laboratory', 8, ['unlock' => ['science_branch_speed']]],
            ['Laboratory', 10, ['unlock' => ['science_branch_control']]],

            // 🏚 Warehouse
            ['Warehouse', 1, ['resource_shielding' => '200']],
            ['Warehouse', 5, ['resource_shielding' => '500']],
            ['Warehouse', 10, ['resource_shielding'=> ['1000']]],
            ['Warehouse', 15, ['resource_shielding' => ['2000']]],
            ['Warehouse', 20, ['resource_shielding' => ['4000']]],

            // 📡 Scanner
            ['Scanner', 5, ['unlock' => ['deep_scan']]],
            ['Scanner', 7, ['unlock' => ['rebel_scan']]],
            ['Scanner', 10, ['unlock' => ['ghost_scan']]],

            // 🛡 Guardian
            ['Guardian', 3, ['unlock' => ['defense_drones']]],
            ['Guardian', 5, ['unlock' => ['shield_dome']]],
            ['Guardian', 8, ['unlock' => ['orbital_cannon']]],
            ['Guardian', 10, ['unlock' => ['return_fire_feature']]],
        ];

        foreach ($data as [$building, $level, $effects]) {
            BuildingLevel::create([
                'building_key' => $building,
                'level' => $level,
                'effects' => $effects,
            ]);
        }
    }
}
