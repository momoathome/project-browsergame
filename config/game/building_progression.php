<?php

return [
    'growth_factors' => [
        'Shipyard' => 1.40,
        'Hangar' => 1.32,
        'Laboratory' => 1.45,
        'Warehouse' => 1.30,
        'Scanner' => 1.38,
        'Shield' => 1.42,
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

    // Ab welchem Level werden neue Ressourcen benötigt
    'resource_thresholds' => [
        'Cobalt' => 5,
        'Iridium' => 10,
        'Uraninite' => 15,
        'Thorium' => 20,
        'Astatine' => 25,
        'Hyperdiamond' => 30,
        'Dilithium' => 35,
        'Deuterium' => 40,
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
        // Weitere Gebäude mit spezifischen Ressourcenanforderungen...
    ],
];
