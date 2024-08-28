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
        'legacy' => ['Titanium', 'Carbon', 'Hydrogenium', 'Kyberkristall'],
        'metal' => ['Titanium', 'Cobalt', 'Iridium'],
        'metal2' => ['Titanium', 'Cobalt', 'Iridium'],
        'metal3' => ['Titanium', 'Cobalt', 'Iridium'],
        'metal4' => ['Titanium', 'Cobalt', 'Iridium'],
        'crystal' => ['Carbon', 'Kyberkristall', 'Hyperdiamond'],
        'crystal2' => ['Carbon', 'Kyberkristall', 'Hyperdiamond'],
        'crystal3' => ['Carbon', 'Kyberkristall', 'Hyperdiamond'],
        'radioactive' => ['Uraninite', 'Thorium', 'Astatine'],
        'radioactive2' => ['Uraninite', 'Thorium', 'Astatine'],
        'exotic' => ['Hydrogenium', 'Dilithium', 'Deuterium'],
        'exotic2' => ['Hydrogenium', 'Dilithium', 'Deuterium'],
        'titanium' => ['Titanium'],
        'carbon' => ['Carbon'],
        'hydrogenium' => ['Hydrogenium'],
        'kyberkristall' => ['Kyberkristall'],
        'cobalt' => ['Cobalt'],
        'iridium' => ['Iridium'],
        'uraninite' => ['Uraninite'],
        'thorium' => ['Thorium'],
    ],

    'pool_resource_weights' => [
        'legacy' => [
            'Titanium' => 0.25,
            'Carbon' => 0.45,
            'Hydrogenium' => 0.2,
            'Kyberkristall' => 0.1,
        ],
        'metal' => [
            'Titanium' => 0.7,
            'Cobalt' => 0.2,
            'Iridium' => 0.1,
        ],
        'metal2' => [
            'Titanium' => 0.4,
            'Cobalt' => 0.4,
            'Iridium' => 0.2,
        ],
        'metal3' => [
            'Titanium' => 0.3,
            'Cobalt' => 0.6,
            'Iridium' => 0.1,
        ],
        'metal4' => [
            'Titanium' => 0.3,
            'Cobalt' => 0.2,
            'Iridium' => 0.5,
        ],
        'crystal' => [
            'Carbon' => 0.75,
            'Kyberkristall' => 0.2,
            'Hyperdiamond' => 0.05,
        ],
        'crystal2' => [
            'Carbon' => 0.55,
            'Kyberkristall' => 0.35,
            'Hyperdiamond' => 0.1,
        ],
        'crystal3' => [
            'Carbon' => 0.4,
            'Kyberkristall' => 0.45,
            'Hyperdiamond' => 0.15,
        ],
        'radioactive' => [
            'Hydrogenium' => 0.6,
            'Uraninite' => 0.25,
            'Thorium' => 0.1,
            'Astatine' => 0.05,
        ],
        'radioactive2' => [
            'Hydrogenium' => 0.4,
            'Uraninite' => 0.3,
            'Thorium' => 0.2,
            'Astatine' => 0.1,
        ],
        'exotic' => [
            'Hydrogenium' => 0.85,
            'Dilithium' => 0.1,
            'Deuterium' => 0.05,
        ],
        'exotic2' => [
            'Hydrogenium' => 0.7,
            'Dilithium' => 0.2,
            'Deuterium' => 0.1,
        ],
        'titanium' => [
            'Titanium' => 1,
        ],
        'carbon' => [
            'Carbon' => 1,
        ],
        'hydrogenium' => [
            'Hydrogenium' => 1,
        ],
        'kyberkristall' => [
            'Kyberkristall' => 1,
        ],
        'cobalt' => [
            'Cobalt' => 1,
        ],
        'iridium' => [
            'Iridium' => 1,
        ],
        'uraninite' => [
            'Uraninite' => 1,
        ],
        'thorium' => [
            'Thorium' => 1,
        ],
    ],
];
