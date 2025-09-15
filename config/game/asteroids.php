<?php

$asteroid_to_station_distance = 1500;

return [
    'asteroid_img_size' => [
        'small' => 4,
        'medium' => 7,
        'large' => 14,
        'extreme' => 24,
    ],

    /* chance to generate of asteroid sizes */
    'asteroid_size' => [
        'small' => 745, // Gewicht für die Zufallsauswahl sollte gesamt 1000 ergeben
        'medium' => 220,
        'large' => 25,
        'extreme' => 10,
    ],

    /* base amount */
    'asteroid_faktor' => [
        'min' => 45,
        'max' => 60,
    ],

    /* base multiplier */
    'asteroid_faktor_multiplier' => [
        'small' => ['min' => 5, 'max' => 8],
        'medium' => ['min' => 13, 'max' => 21],
        'large' => ['min' => 34, 'max' => 55],
        'extreme' => ['min' => 89, 'max' => 144],
    ],

    // Multiplikator Bestimmt, wie weit Asteroiden von Stationen entfernt sein müssen
    'size_min_distance' => [
        'base' => $asteroid_to_station_distance,
        'small_asteroid' => 1.0,
        'medium_asteroid' => 5.0,
        'large_asteroid' => 10.0,
        'extreme_asteroid' => 18.0,
    ],

    'resource_min_distances' => [
        'base' => $asteroid_to_station_distance,
        'low_value' => 1.0,
        'medium_value' => 7.0,
        'high_value' => 15.0,
        'extreme_value' => 22.0,
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
        'low_value' => 0.7575, //75.75%
        'medium_value' => 0.20, //20%
        'high_value' => 0.035, //3.5%
        'extreme_value' => 0.0075, //0.75%
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],
];
