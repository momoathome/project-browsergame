<?php

$build_time_multiplier = 1.30;
$additional_resource_base_value = 10;
$additional_resources_multiplier = 1;
$additional_resource_referenz = 50;

return [
    'build_time_multiplier' => $build_time_multiplier,
    'additional_resource_base_value' => $additional_resource_base_value,
    'additional_resources_multiplier' => $additional_resources_multiplier,
    'additional_resource_referenz' => $additional_resource_referenz,

    // general resource growth factors per building type
    'growth_factors' => [
        'Core' => 1.275,
        'Shipyard' => 1.30,
        'Hangar' => 1.275,
        'Laboratory' => 1.275,
        'Warehouse' => 1.275,
        'Scanner' => 1.275,
        'Guardian' => 1.30,
        // Weitere Gebäude...
    ],

    // Milestone Multipliers resource requirements at specific levels
    'milestone_multipliers' => [
        5 => 1.1,   // Level 5: 10% extra
        10 => 1.2,  // Level 10: 20% extra
        15 => 1.3,  // Level 15: 30% extra
        20 => 1.4,  // Level 20: 40% extra
        25 => 1.5,  // Level 25: 50% extra
        30 => 1.6,  // Level 30: 60% extra
    ],

    // Ressourcenanforderungen nach Gebäudetyp
    'building_resources' => [
        'Core' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Shipyard' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Hangar' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Laboratory' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Warehouse' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Scanner' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Guardian' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_4' => ['Cobalt'],
            'level_7' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        // Weitere Gebäude mit spezifischen Ressourcenanforderungen...
    ],

    'effect_configs' => [
        'Core' => [
            'type' => 'additive',
            'base_value' => 1.0,
            'increment' => 0.05,
        ],
        'Shipyard' => [
            'type' => 'additive',
            'base_value' => 1.0,
            'increment' => 0.05,
        ],
        'Hangar' => [
            'type' => 'exponential',
            'base_value' => 15,
            'increment' => 1.375,
        ],
        'Warehouse' => [
            'type' => 'exponential',
            'base_value' => 1500,
            'increment' => 1.235,
        ],
        'Laboratory' => [
            'type' => 'additive',
            'base_value' => 0,
            'increment' => 3,
        ],
        'Scanner' => [
            'type' => 'additive',
            'base_value' => 4000,
            'increment' => 2000,
        ],
        'Guardian' => [
            'type' => 'additive',
            'base_value' => 1.0,
            'increment' => 0.02,
        ],
    ],

    'effect_attributes' => [
        'Core' => ['upgrade_speed'],
        'Shipyard' => ['production_speed'],
        'Hangar' => ['crew_limit'],
        'Warehouse' => ['storage'],
        'Laboratory' => ['research_points'],
        'Scanner' => ['scan_range'],
        'Guardian' => ['base_defense'],
    ],
    
    'building_level_effects' => [
        'Core' => [
            1 => ['building_slots' => 1],
            3 => ['building_slots' => 2],
            5 => ['building_slots' => 3],
            10 => ['building_slots' => 4],
            15 => ['building_slots' => 5],
        ],
        'Shipyard' => [
            1 => ['production_slots' => 1, 'unlock' => ['Mole']],
            2 => ['unlock' => ['Merlin']],
            3 => ['production_slots' => 2],
            4 => ['unlock' => ['Nomad']],
            5 => ['production_slots' => 3, 'unlock' => ['Comet']],
            7 => ['unlock' => ['Javelin']],
            8 => ['production_slots' => 4, 'unlock' => ['Sentinel']],
            9 => ['unlock' => ['Titan']],
            10 => ['production_slots' => 5, 'unlock' => ['Probe']],
            12 => ['unlock' => ['Hercules']],
            14 => ['unlock' => ['Ares']],
            15 => ['production_slots' => 6, 'unlock' => ['Nova']],
            18 => ['unlock' => ['Horus']],
            20 => ['unlock' => ['Reaper']],
        ],
        'Hangar' => [
            1 => ['dock_slots' => 2],
            2 => ['dock_slots' => 5],
            3 => ['dock_slots' => 8],
            4 => ['dock_slots' => 12],
            5 => ['dock_slots' => 15, 'unlock' => 'auto_mining'],
            6 => ['dock_slots' => 20],
            7 => ['dock_slots' => 25],
            8 => ['dock_slots' => 30],
            9 => ['dock_slots' => 40],
            10 => ['dock_slots' => 50],
            11 => ['dock_slots' => 60],
            12 => ['dock_slots' => 75],
            13 => ['dock_slots' => 90],
            14 => ['dock_slots' => 105],
            15 => ['dock_slots' => 120],
            16 => ['dock_slots' => 140],
            17 => ['dock_slots' => 160],
            18 => ['dock_slots' => 180],
            19 => ['dock_slots' => 200],
            20 => ['dock_slots' => 225],
        ],
        'Laboratory' => [
            5 => ['unlock' => ['science_branch_industry']],
            8 => ['unlock' => ['science_branch_speed']],
            10 => ['unlock' => ['science_branch_control']],
        ],
        'Warehouse' => [
            1 => ['resource_shielding' => '200'],
            2 => ['resource_shielding' => '250'],
            3 => ['resource_shielding' => '300'],
            4 => ['resource_shielding' => '400'],
            5 => ['resource_shielding' => '500'],
            6 => ['resource_shielding' => '600'],
            7 => ['resource_shielding' => '700'],
            8 => ['resource_shielding' => '800'],
            9 => ['resource_shielding' => '900'],
            10 => ['resource_shielding' => '1000'],
            12 => ['resource_shielding' => '1500'],
            15 => ['resource_shielding' => '2000'],
            18 => ['resource_shielding' => '3000'],
            20 => ['resource_shielding' => '4000'],
        ],
        'Scanner' => [
            5 => ['unlock' => ['deep_scan']],
            7 => ['unlock' => ['rebel_scan']],
            10 => ['unlock' => ['ghost_scan']],
        ],
        'Guardian' => [
            3 => ['unlock' => ['defense_drones']], // +10% base defense
            5 => ['unlock' => ['shield_dome']], // +15% base defense
            8 => ['unlock' => ['laser_turret']], // +20% base defense
            10 => ['unlock' => ['double_tower']], // x2 defense
            12 => ['unlock' => ['guardian_missiles']], // +30% base defense
            15 => ['unlock' => ['triple_tower']], // x2 defense
            20 => ['unlock' => ['last_stand']], // x2 defense
        ],
    ],
    
];
