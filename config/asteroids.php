<?php

$asteroid_count = 1000;
$asteroid_density = 60;
$min_distance = 1000;
$universe_size = $asteroid_count * $asteroid_density;

return [
    'asteroid_count' => $asteroid_count,
    'asteroid_density' => $asteroid_density,
    'min_distance' => $min_distance,
    'universe_size' => $universe_size,

    'asteroid_size' => [
        'common' => 1,
        'uncommon' => 2,
        'rare' => 4,
        'extreme' => 8,
    ],

    'asteroid_faktor' => [
        'min' => 90,
        'max' => 125,
    ],

    'asteroid_rarity' => [
        'common' => 700,
        'uncommon' => 300,
        'rare' => 25,
        'extreme' => 5,
    ],

    'asteroid_faktor_multiplier' => [
        'common' => ['min' => 5, 'max' => 8],
        'uncommon' => ['min' => 13, 'max' => 21],
        'rare' => ['min' => 34, 'max' => 55],
        'extreme' => ['min' => 89, 'max' => 144],
    ],

    'distance_modifiers' => [
        'common' => 0,
        'uncommon' => 0,
        'rare' => 4 * $min_distance,
        'extreme' => 10 * $min_distance,
    ],

    'resource_pools' => [
        'low_value' => [
            'resources' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'resource_distance_modifier' => 0,
        ],
        'medium_value' => [
            'resources' => ['Cobalt', 'Iridium', 'Astatine', 'Uraninite', 'Thorium'],
            'resource_distance_modifier' => 5 * $min_distance,
        ],
        'high_value' => [
            'resources' => ['Hyperdiamond', 'Dilithium', 'Deuterium'],
            'resource_distance_modifier' => 12 * $min_distance,
        ],
    ],

    'pool_weights' => [
        'low_value' => 0.6,
        'medium_value' => 0.3,
        'high_value' => 0.1,
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],

    /* 'resource_pools' => [
        'legacy' => [
            'resources' => ['Titanium', 'Carbon', 'Hydrogenium', 'Kyberkristall'],
        ],
        'metal' => [
            'resources' => ['Titanium', 'Cobalt'],
        ],
        'metal2' => [
            'resources' => ['Titanium', 'Cobalt', 'Astatine'],
        ],
        'metal3' => [
            'resources' => ['Titanium', 'Iridium', 'Astatine'],
        ],
        'metal4' => [
            'resources' => ['Titanium', 'Cobalt', 'Iridium'],
        ],
        'metal5' => [
            'resources' => ['Cobalt', 'Iridium', 'Astatine'],
        ],
        'radioactive' => [
            'resources' => ['Titanium', 'Uraninite', 'Astatine'],
        ],
        'radioactive2' => [
            'resources' => ['Hydrogenium', 'Uraninite', 'Thorium'],
        ],
        'radioactive3' => [
            'resources' => ['Iridium', 'Uraninite', 'Thorium'],
        ],
        'crystal' => [
            'resources' => ['Carbon', 'Kyberkristall', 'Hyperdiamond'],
        ],
        'crystal2' => [
            'resources' => ['Carbon', 'Kyberkristall', 'Hyperdiamond'],
        ],
        'crystal3' => [
            'resources' => ['Carbon', 'Kyberkristall', 'Hyperdiamond'],
        ],
        'exotic' => [
            'resources' => ['Hydrogenium', 'Dilithium', 'Deuterium'],
        ],
        'exotic2' => [
            'resources' => ['Hydrogenium', 'Dilithium', 'Deuterium'],
        ],
        'exotic3' => [
            'resources' => ['Kyberkristall', 'Dilithium', 'Deuterium'],
        ],
        'titanium' => [
            'resources' => ['Titanium'],
        ],
        'carbon' => [
            'resources' => ['Carbon'],
        ],
        'hydrogenium' => [
            'resources' => ['Hydrogenium'],
        ],
        'kyberkristall' => [
            'resources' => ['Kyberkristall'],
        ],
        'cobalt' => [
            'resources' => ['Cobalt'],
        ],
        'astatine' => [
            'resources' => ['Astatine'],
        ],
        'iridium' => [
            'resources' => ['Iridium'],
        ],
        'uraninite' => [
            'resources' => ['Uraninite'],
        ],
        'thorium' => [
            'resources' => ['Thorium'],
        ],
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
            'Cobalt' => 0.3,
        ],
        'metal2' => [
            'Titanium' => 0.4,
            'Cobalt' => 0.4,
            'Astatine' => 0.2,
        ],
        'metal3' => [
            'Titanium' => 0.4,
            'Astatine' => 0.4,
            'Iridium' => 0.2,
        ],
        'metal4' => [
            'Titanium' => 0.4,
            'Cobalt' => 0.3,
            'Iridium' => 0.3,
        ],
        'metal5' => [
            'Cobalt' => 0.4,
            'Iridium' => 0.3,
            'Astatine' => 0.3,
        ],
        'radioactive' => [
            'Titanium' => 0.5,
            'Uraninite' => 0.25,
            'Astatine' => 0.25,
        ],
        'radioactive2' => [
            'Hydrogenium' => 0.4,
            'Uraninite' => 0.4,
            'Thorium' => 0.2,
        ],
        'radioactive3' => [
            'Iridium' => 0.3,
            'Uraninite' => 0.4,
            'Thorium' => 0.3,
        ],
        'crystal' => [
            'Carbon' => 0.7,
            'Kyberkristall' => 0.2,
            'Hyperdiamond' => 0.1,
        ],
        'crystal2' => [
            'Carbon' => 0.5,
            'Kyberkristall' => 0.3,
            'Hyperdiamond' => 0.2,
        ],
        'crystal3' => [
            'Carbon' => 0.4,
            'Kyberkristall' => 0.4,
            'Hyperdiamond' => 0.2,
        ],
        'exotic' => [
            'Hydrogenium' => 0.8,
            'Dilithium' => 0.1,
            'Deuterium' => 0.1,
        ],
        'exotic2' => [
            'Hydrogenium' => 0.65,
            'Dilithium' => 0.25,
            'Deuterium' => 0.2,
        ],
        'exotic3' => [
            'Kyberkristall' => 0.4,
            'Dilithium' => 0.3,
            'Deuterium' => 0.3,
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
        'astatine' => [
            'Astatine' => 1,
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
    ], */
];
