<?php

$asteroid_count = 2000;
$asteroid_density = 55; // smaller number = more dense because smaller universe_size
$min_distance = 1000;
$station_to_station_distance = 5000;
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

    /* base amount */
    'asteroid_faktor' => [
        'min' => 90,
        'max' => 125,
    ],

    /* chance to generate of asteroid sizes */
    'asteroid_size' => [
        'small' => 750, // chance of $asteroid_count
        'medium' => 210,
        'large' => 30,
        'extreme' => 10,
    ],

    'asteroid_faktor_multiplier' => [
        'small' => ['min' => 5, 'max' => 8],
        'medium' => ['min' => 13, 'max' => 21],
        'large' => ['min' => 34, 'max' => 55],
        'extreme' => ['min' => 89, 'max' => 144],
    ],

    'distance_modifiers' => [
        'small' => 0,
        'medium' => 2 * $min_distance,
        'large' => 8 * $min_distance,
        'extreme' => 15 * $min_distance,
    ],

    'resource_pools' => [
        'low_value' => [
            'resources' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
            'resource_distance_modifier' => 1 * $min_distance,
        ],
        'medium_value' => [
            'resources' => ['Cobalt', 'Iridium', 'Uraninite'],
            'resource_distance_modifier' => 12 * $min_distance,
        ],
        'high_value' => [
            'resources' => ['Thorium', 'Astatine', 'Hyperdiamond'],
            'resource_distance_modifier' => 20 * $min_distance,
        ],
        'extreme_value' => [
            'resources' => ['Dilithium', 'Deuterium'],
            'resource_distance_modifier' => 25 * $min_distance,
        ],
    ],

    'pool_weights' => [
        'low_value' => 0.60, //60%
        'medium_value' => 0.35, //35%
        'high_value' => 0.04, //4%
        'extreme_value' => 0.01, //1%
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],
];
