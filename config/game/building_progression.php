<?php

$build_time_multiplier = 1.35;
$additional_resource_base_value = 100;
$additional_resources_multiplier = 1;
$additional_resource_referenz = 1000;

return [
    'build_time_multiplier' => $build_time_multiplier,
    'additional_resource_base_value' => $additional_resource_base_value,
    'additional_resources_multiplier' => $additional_resources_multiplier,
    'additional_resource_referenz' => $additional_resource_referenz,

    'growth_factors' => [
        'Shipyard' => 1.35,
        'Hangar' => 1.275,
        'Laboratory' => 1.25,
        'Warehouse' => 1.30,
        'Scanner' => 1.30,
        'Shield' => 1.375,
        // Weitere Gebäude...
    ],

    // Zusätzliche Multiplikatoren an bestimmten Meilensteinen
    'milestone_multipliers' => [
        5 => 1.2,   // Level 5: 20% extra
        10 => 1.3,  // Level 10: 30% extra
        14 => 1.5,  // Level 15: 50% extra
        18 => 1.8,  // Level 20: 80% extra
        21 => 2.0,  // Level 25: 100% extra
        25 => 2.5,  // Level 30: 150% extra
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
];
