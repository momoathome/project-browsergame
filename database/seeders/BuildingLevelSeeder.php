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
            ['Shipyard', 3, ['production_slots' => 2]],
            ['Shipyard', 4, ['unlock' => ['Nomad']]],
            ['Shipyard', 5, ['production_slots' => 3, 'unlock' => ['Comet']]],
            ['Shipyard', 7, ['unlock' => ['Javelin']]],
            ['Shipyard', 8, ['production_slots' => 4, 'unlock' => ['Sentinel']]],
            ['Shipyard', 9, ['unlock' => ['Titan']]],
            ['Shipyard', 10, ['production_slots' => 5, 'unlock' => ['Probe']]],
            ['Shipyard', 12, ['unlock' => ['Hercules']]],
            ['Shipyard', 14, ['unlock' => ['Ares']]],
            ['Shipyard', 15, ['production_slots' => 6, 'unlock' => ['Nova']]],
            ['Shipyard', 18, ['unlock' => ['Horus']]],
            ['Shipyard', 20, ['unlock' => ['Reaper']]],

            // 🛩 Hangar
            ['Hangar', 1, ['dock_slots' => 2]],
            ['Hangar', 2, ['dock_slots' => 5]],
            ['Hangar', 3, ['dock_slots' => 8]],
            ['Hangar', 4, ['dock_slots' => 12]],
            ['Hangar', 5, ['dock_slots' => 15, 'unlock' => 'auto_mining']],
            ['Hangar', 6, ['dock_slots' => 20]],
            ['Hangar', 7, ['dock_slots' => 25]],
            ['Hangar', 8, ['dock_slots' => 30]],
            ['Hangar', 9, ['dock_slots' => 40]],
            ['Hangar', 10, ['dock_slots' => 50]],
            ['Hangar', 11, ['dock_slots' => 60]],
            ['Hangar', 12, ['dock_slots' => 75]],
            ['Hangar', 13, ['dock_slots' => 90]],
            ['Hangar', 14, ['dock_slots' => 105]],
            ['Hangar', 15, ['dock_slots' => 120]],
            ['Hangar', 16, ['dock_slots' => 140]],
            ['Hangar', 17, ['dock_slots' => 160]],
            ['Hangar', 18, ['dock_slots' => 180]],
            ['Hangar', 19, ['dock_slots' => 200]],
            ['Hangar', 20, ['dock_slots' => 225]],

            // 🧪 Laboratory
            ['Laboratory', 5, ['unlock' => ['science_branch_industry']]],
            ['Laboratory', 8, ['unlock' => ['science_branch_speed']]],
            ['Laboratory', 10, ['unlock' => ['science_branch_control']]],

            // 🏚 Warehouse
            ['Warehouse', 1, ['resource_shielding' => '200']],
            ['Warehouse', 2, ['resource_shielding' => '250']],
            ['Warehouse', 3, ['resource_shielding' => '300']],
            ['Warehouse', 4, ['resource_shielding' => '400']],
            ['Warehouse', 5, ['resource_shielding' => '500']],
            ['Warehouse', 6, ['resource_shielding' => '600']],
            ['Warehouse', 7, ['resource_shielding' => '700']],
            ['Warehouse', 8, ['resource_shielding' => '800']],
            ['Warehouse', 9, ['resource_shielding' => '900']],
            ['Warehouse', 10, ['resource_shielding'=> '1000']],
            ['Warehouse', 12, ['resource_shielding' => '1500']],
            ['Warehouse', 15, ['resource_shielding' => '2000']],
            ['Warehouse', 18, ['resource_shielding' => '3000']],
            ['Warehouse', 20, ['resource_shielding' => '4000']],

            // 📡 Scanner
            ['Scanner', 5, ['unlock' => ['deep_scan']]],
            ['Scanner', 7, ['unlock' => ['rebel_scan']]],
            ['Scanner', 10, ['unlock' => ['ghost_scan']]],

            // 🛡 Guardian - has base defense each upgrade increases additive the base value
            ['Guardian', 3, ['unlock' => ['defense_drones']]], // +10% base defense
            ['Guardian', 5, ['unlock' => ['shield_dome']]], // +15% base defense
            ['Guardian', 8, ['unlock' => ['laser_turret']]], // +20% base defense
            ['Guardian', 10, ['unlock' => ['double_tower']]], // x2 defense
            ['Guardian', 12, ['unlock' => ['guardian_missiles']]], // +30% base defense
            ['Guardian', 15, ['unlock' => ['triple_tower']]], // x2 defense
            ['Guardian', 20, ['unlock' => ['last_stand']]], // x2 defense
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
