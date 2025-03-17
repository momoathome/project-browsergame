<?php

$asteroid_count = 10000;
$asteroid_density = 40; // kleinere Zahl = dichter besiedelt
$min_distance_between_asteroids = 1000;
$station_to_station_distance = 5000;
$universe_size = $asteroid_count * $asteroid_density;

return [
    'asteroid_count' => $asteroid_count,
    'asteroid_density' => $asteroid_density,
    'min_distance_between_asteroids' => $min_distance_between_asteroids,
    'station_to_station_distance' => $station_to_station_distance,
    'universe_size' => $universe_size,
    
    // Koordinatenbereich, in dem Asteroiden generiert werden können
    'spawn_area' => [
        'min_x' => 0,
        'min_y' => 0,
        'max_x' => $universe_size,
        'max_y' => $universe_size,
    ],

    // Bestimmt, wie weit Asteroiden von Stationen entfernt sein müssen
    'station_safety_distance' => [
        'base' => 1000,
        'small_asteroid' => 2.0,
        'medium_asteroid' => 4.0,
        'large_asteroid' => 8.0,
        'extreme_asteroid' => 15.0,
    ],

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
        'small' => 750, // Gewicht für die Zufallsauswahl
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

    'resource_min_distances' => [
        'low_value' => 1 * $min_distance_between_asteroids,
        'medium_value' => 12 * $min_distance_between_asteroids,
        'high_value' => 20 * $min_distance_between_asteroids,
        'extreme_value' => 25 * $min_distance_between_asteroids,
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
        'low_value' => 0.65, //60%
        'medium_value' => 0.30, //30%
        'high_value' => 0.04, //4%
        'extreme_value' => 0.01, //1%
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],
];
