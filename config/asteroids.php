<?php

$asteroid_count = 1000;
$asteroid_density = 60;
$min_distance = 1000;
$station_to_station_distance = 3000;
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
        'low_value' => 0.55,
        'medium_value' => 0.45,
        'high_value' => 0.1,
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],
];
