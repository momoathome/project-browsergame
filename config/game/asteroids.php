<?php

$config = config('game.core');
$asteroid_to_station_distance = $config['asteroid_to_station_distance'] ?? 300;

return [
    'asteroid_img_size' => [
        'small' => 1,
        'medium' => 2,
        'large' => 4,
        'extreme' => 8,
    ],

    /* chance to generate of asteroid sizes */
    'asteroid_size' => [
        'small' => 750, // Gewicht für die Zufallsauswahl
        'medium' => 220,
        'large' => 20,
        'extreme' => 10,
    ],

    /* base amount */
    'asteroid_faktor' => [
        'min' => 80,
        'max' => 125,
    ],

    /* base multiplier */
    'asteroid_faktor_multiplier' => [
        'small' => ['min' => 5, 'max' => 8],
        'medium' => ['min' => 13, 'max' => 21],
        'large' => ['min' => 34, 'max' => 55],
        'extreme' => ['min' => 89, 'max' => 144],
    ],

    // Bestimmt, wie weit Asteroiden von Stationen entfernt sein müssen
    'size_min_distance' => [
        'base' => $asteroid_to_station_distance,
        'small_asteroid' => 1.0,
        'medium_asteroid' => 4.0,
        'large_asteroid' => 20.0,
        'extreme_asteroid' => 40.0,
    ],

    'resource_min_distances' => [
        'base' => $asteroid_to_station_distance,
        'low_value' => 1.0,
        'medium_value' => 15.0,
        'high_value' => 30.0,
        'extreme_value' => 40.0,
    ],

    'resource_pools' => [
        'low_value' => [
            'resources' => ['Carbon', 'Titanium', 'Hydrogenium', 'Kyberkristall'],
        ],
        'medium_value' => [
            'resources' => ['Cobalt', 'Iridium', 'Uraninite'],
        ],
        'high_value' => [
            'resources' => ['Thorium', 'Astatine', 'Hyperdiamond'],
        ],
        'extreme_value' => [
            'resources' => ['Dilithium', 'Deuterium'],
        ],
    ],

    'pool_weights' => [
        'low_value' => 0.75, //75%
        'medium_value' => 0.21, //21%
        'high_value' => 0.03, //3%
        'extreme_value' => 0.01, //1%
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],
];
