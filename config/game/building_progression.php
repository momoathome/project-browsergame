<?php

$build_time_multiplier = 1.35;
$additional_resource_base_value = 100;

return [
    'build_time_multiplier' => $build_time_multiplier,
    'additional_resource_base_value' => $additional_resource_base_value,

    'growth_factors' => [
        'Shipyard' => 1.30,
        'Hangar' => 1.25,
        'Laboratory' => 1.40,
        'Warehouse' => 1.25,
        'Scanner' => 1.35,
        'Shield' => 1.40,
        // Weitere Gebäude...
    ],

    // Zusätzliche Multiplikatoren an bestimmten Meilensteinen
    'milestone_multipliers' => [
        5 => 1.2,   // Level 5: 20% extra
        10 => 1.3,  // Level 10: 30% extra
        15 => 1.5,  // Level 15: 50% extra
        20 => 1.8,  // Level 20: 80% extra
        25 => 2.0,  // Level 25: 100% extra
        30 => 2.5,  // Level 30: 150% extra
    ],

    // Ressourcenanforderungen nach Gebäudetyp
    'building_resources' => [
        'Shipyard' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_10' => ['Iridium'],
            'level_15' => ['Uraninite'],
            'level_20' => ['Thorium'],
            'level_25' => ['Hyperdiamond'],
            'level_30' => ['Dilithium'],
        ],
        'Laboratory' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_10' => ['Iridium', 'Uraninite'],
            'level_15' => ['Astatine'],
            'level_20' => ['Hyperdiamond'],
            'level_25' => ['Dilithium', 'Deuterium'],
        ],
        'Hangar' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_10' => ['Iridium'],
            'level_15' => ['Uraninite'],
            'level_20' => ['Thorium'],
            'level_25' => ['Hyperdiamond'],
            'level_30' => ['Dilithium'],
        ],
        'Warehouse' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_10' => ['Iridium'],
            'level_15' => ['Uraninite'],
            'level_20' => ['Thorium'],
            'level_25' => ['Hyperdiamond'],
            'level_30' => ['Dilithium'],
        ],
        'Scanner' => [
            'base' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'level_5' => ['Cobalt'],
            'level_10' => ['Iridium'],
            'level_15' => ['Uraninite'],
            'level_20' => ['Thorium'],
            'level_25' => ['Hyperdiamond'],
            'level_30' => ['Dilithium'],
        ],
        // Weitere Gebäude mit spezifischen Ressourcenanforderungen...
    ],
];
