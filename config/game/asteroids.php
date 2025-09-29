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
        'small' => 785, // Gewicht für die Zufallsauswahl sollte gesamt 1000 ergeben
        'medium' => 180,
        'large' => 25,
        'extreme' => 10,
    ],

    /* base amount */
    'asteroid_faktor' => [
        'min' => 22,
        'max' => 30,
    ],

    /* base multiplier */
    'asteroid_faktor_multiplier' => [
        'small' => ['min' => 5, 'max' => 8],
        'medium' => ['min' => 13, 'max' => 21],
        'large' => ['min' => 34, 'max' => 55],
        'extreme' => ['min' => 89, 'max' => 144],
    ],

    // Anteil der Ressourcen am Asteroiden pro size
    'max_resource_share' => [
        'small' => [
            'low_value' => 1.0,     // 100% erlaubt
            'medium_value' => 0.7,  // max 70% der Gesamtmenge
            'high_value' => 0.5,    // max 50%
            'extreme_value' => 0.075, // max 7.5% auf Extreme
        ],
        'medium' => [
            'low_value' => 1.0,
            'medium_value' => 0.7,
            'high_value' => 0.2,
            'extreme_value' => 0.05, // max 5% auf Medium
        ],
        'large' => [
            'low_value' => 1.0,
            'medium_value' => 0.7,
            'high_value' => 0.1,
            'extreme_value' => 0.025, // max 2.5% auf Large
        ],
        'extreme' => [
            'low_value' => 1.0,
            'medium_value' => 0.7,
            'high_value' => 0.05,
            'extreme_value' => 0.02, // max 2% auf Extreme
        ],
    ],


    // Multiplikator Bestimmt, wie weit Asteroiden von Stationen entfernt sein müssen
    'size_min_distance' => [
        'base' => $asteroid_to_station_distance,
        'small_asteroid' => 1.0,
        'medium_asteroid' => 3.0,
        'large_asteroid' => 6.0,
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
        'low_value' => 0.7725, //77.25%
        'medium_value' => 0.20, //20%
        'high_value' => 0.02, //2%
        'extreme_value' => 0.0075, //0.75%
    ],

    'num_resource_range' => [1, 4],
    'resource_ratio_range' => [10, 100],
];
