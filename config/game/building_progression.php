<?php

$build_time_multiplier = 1.30;
$additional_resource_base_value = 100;
$additional_resources_multiplier = 1;
$additional_resource_referenz = 1000;

return [
    'build_time_multiplier' => $build_time_multiplier,
    'additional_resource_base_value' => $additional_resource_base_value,
    'additional_resources_multiplier' => $additional_resources_multiplier,
    'additional_resource_referenz' => $additional_resource_referenz,

    // general resource growth factors per building type
    'growth_factors' => [
        'Shipyard' => 1.30,
        'Hangar' => 1.275,
        'Laboratory' => 1.30,
        'Warehouse' => 1.275,
        'Scanner' => 1.25,
        'Shield' => 1.30,
        // Weitere Gebäude...
    ],

    // Milestone Multipliers resource requirements at specific levels
    'milestone_multipliers' => [
        5 => 1.2,   // Level 5: 20% extra
        10 => 1.3,  // Level 10: 30% extra
        15 => 1.4,  // Level 15: 40% extra
        20 => 1.5,  // Level 20: 50% extra
        25 => 1.75,  // Level 25: 75% extra
        30 => 2.0,  // Level 30: 100% extra
    ],

    // Ressourcenanforderungen nach Gebäudetyp
    'building_resources' => [
        'Shipyard' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_3' => ['Cobalt'],
            'level_5' => ['Iridium'],
            'level_8' => ['Uraninite'],
            'level_12' => ['Thorium'],
            'level_16' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_22' => ['Dilithium'],
        ],
        'Hangar' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_3' => ['Cobalt'],
            'level_5' => ['Iridium'],
            'level_8' => ['Uraninite'],
            'level_12' => ['Thorium'],
            'level_16' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_22' => ['Dilithium'],
        ],
        'Laboratory' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_3' => ['Cobalt'],
            'level_5' => ['Iridium'],
            'level_8' => ['Uraninite'],
            'level_12' => ['Thorium'],
            'level_16' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_22' => ['Dilithium'],
        ],
        'Warehouse' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_3' => ['Cobalt'],
            'level_5' => ['Iridium'],
            'level_8' => ['Uraninite'],
            'level_12' => ['Thorium'],
            'level_16' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_22' => ['Dilithium'],
        ],
        'Scanner' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_3' => ['Cobalt'],
            'level_5' => ['Iridium'],
            'level_8' => ['Uraninite'],
            'level_12' => ['Thorium'],
            'level_16' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_22' => ['Dilithium'],
        ],
        'Shield' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_3' => ['Cobalt'],
            'level_5' => ['Iridium'],
            'level_8' => ['Uraninite'],
            'level_12' => ['Thorium'],
            'level_16' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_22' => ['Dilithium'],
        ],
        // Weitere Gebäude mit spezifischen Ressourcenanforderungen...
    ],

    'effect_configs' => [
        'Shipyard' => [
            'type' => 'multiplicative',
            'base_value' => 1,
            'increment' => 0.05,
        ],
        'Hangar' => [
            'type' => 'exponential',
            'base_value' => 10,
            'increment' => 1.375,
        ],
        'Warehouse' => [
            'type' => 'exponential',
            'base_value' => 1500,
            'increment' => 1.275,
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
        'Shield' => [
            'type' => 'multiplicative',
            'base_value' => 1.0,
            'increment' => 0.05,
        ],
    ],

    'effect_attributes' => [
        'Shipyard' => ['production_speed'],
        'Hangar' => ['crew_limit'],
        'Warehouse' => ['storage'],
        'Laboratory' => ['research_points'],
        'Scanner' => ['scan_range'],
        'Shield' => ['base_defense'],
    ],
];
