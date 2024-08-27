<?php

$asteroid_count = 2000;
$asteroid_density = 50;
$min_distance = 1000;
$station_radius = 2000;

return [
    'asteroid_count' => $asteroid_count,
    'asteroid_density' => $asteroid_density,
    'min_distance' => $min_distance,
    'station_radius' => $station_radius,
    'universe_size' => $asteroid_count * $asteroid_density,

    'asteroid_size' => [
        'common' => 1,
        'uncommen' => 2,
        'rare' => 4,
        'extrem' => 8,
    ],

    'asteroid_faktor' => [
        'min' => 55,
        'max' => 90,
    ],

    'asteroid_rarity' => [
        'common' => 800,
        'uncommen' => 250,
        'rare' => 50,
        'extrem' => 3,
    ],

    'asteroid_rarity_multiplier' => [
        'common' => ['min' => 5, 'max' => 8],
        'uncommen' => ['min' => 13, 'max' => 21],
        'rare' => ['min' => 34, 'max' => 55],
        'extrem' => ['min' => 89, 'max' => 144],
    ],

    'distance_modifiers' => [
        'common' => $min_distance,
        'uncommen' => $min_distance,
        'rare' => 3 * $min_distance,
        'extrem' => 10 * $min_distance,
    ],

    'resource_pools' => [
        'legacy' => ['titanium', 'carbon', 'hydrogenium', 'kyberkristall'],
        'metal' => ['titanium', 'cobalt', 'iridium'],
        'crystal' => ['carbon', 'kyberkristall', 'hyperdiamond'],
        'radioactive' => ['uraninite', 'thorium', 'astatine'],
        'exotic' => ['hydrogenium', 'dilithium', 'deuterium'],
    ],

    'pool_resource_weights' => [
        'legacy' => [
            'titanium' => 0.25,
            'carbon' => 0.45,
            'hydrogenium' => 0.2,
            'kyberkristall' => 0.1,
        ],
        'metal' => [
            'titanium' => 0.7,
            'cobalt' => 0.2,
            'iridium' => 0.1,
        ],
        'metal2' => [
            'titanium' => 0.4,
            'cobalt' => 0.4,
            'iridium' => 0.2,
        ],
        'crystal' => [
            'carbon' => 0.75,
            'kyberkristall' => 0.2,
            'hyperdiamond' => 0.05,
        ],
        'crystal2' => [
            'carbon' => 0.55,
            'kyberkristall' => 0.35,
            'hyperdiamond' => 0.1,
        ],
        'radioactive' => [
            'hydrogenium' => 0.6,
            'uraninite' => 0.25,
            'thorium' => 0.1,
            'astatine' => 0.05,
        ],
        'radioactive2' => [
            'hydrogenium' => 0.4,
            'uraninite' => 0.3,
            'thorium' => 0.2,
            'astatine' => 0.1,
        ],
        'exotic' => [
            'hydrogenium' => 0.85,
            'dilithium' => 0.1,
            'deuterium' => 0.05,
        ],
    ],
];
