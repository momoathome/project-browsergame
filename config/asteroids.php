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

    'asteroid_img_size' => [
        'small' => 1,
        'medium' => 2,
        'large' => 4,
        'extreme' => 8,
    ],

    'asteroid_faktor' => [
        'min' => 90,
        'max' => 125,
    ],

    'asteroid_size' => [
        'small' => 700,
        'medium' => 300,
        'large' => 25,
        'extreme' => 5,
    ],

    'asteroid_faktor_multiplier' => [
        'small' => ['min' => 5, 'max' => 8],
        'medium' => ['min' => 13, 'max' => 21],
        'large' => ['min' => 34, 'max' => 55],
        'extreme' => ['min' => 89, 'max' => 144],
    ],

    'distance_modifiers' => [
        'small' => 0,
        'medium' => 0,
        'large' => 4 * $min_distance,
        'extreme' => 10 * $min_distance,
    ],

    'resource_pools' => [
        'low_value' => [
            'resources' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'resource_distance_modifier' => 0,
        ],
        'medium_value' => [
            'resources' => ['Cobalt', 'Iridium', 'Uraninite', 'Thorium'],
            'resource_distance_modifier' => 5 * $min_distance,
        ],
        'high_value' => [
            'resources' => ['Astatine', 'Hyperdiamond'],
            'resource_distance_modifier' => 12 * $min_distance,
        ],
        'extreme_value' => [
            'resources' => ['Dilithium', 'Deuterium'],
            'resource_distance_modifier' => 16 * $min_distance,
        ],
    ],

    'pool_weights' => [
        'low_value' => 0.60, //60%
        'medium_value' => 0.34, //34%
        'high_value' => 0.05, //5%
        'extreme_value' => 0.01, //1%
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [5, 100],
];
