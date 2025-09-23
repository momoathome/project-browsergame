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
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Shipyard' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Hangar' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Laboratory' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Warehouse' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Scanner' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
            'level_10' => ['Uraninite'],
            'level_15' => ['Thorium'],
            'level_18' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium'],
            'level_30' => ['Deuterium'],
        ],
        'Guardian' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_8' => ['Iridium'],
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
];
