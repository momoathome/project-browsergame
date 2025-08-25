<?php

return [
    'buildings' => [
        [
            'name' => 'Shipyard',
            'details_id' => 1,
            'effect_value' => 1, // Basiswert für Level 1
            'build_time' => 60,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 300],
                ['resource_name' => 'Titanium', 'amount' => 400],
                ['resource_name' => 'Hydrogenium', 'amount' => 250],
                ['resource_name' => 'Kyberkristall', 'amount' => 150],
            ],
        ],
        [
            'name' => 'Hangar',
            'details_id' => 2,
            'effect_value' => 10,
            'build_time' => 60,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 250],
                ['resource_name' => 'Titanium', 'amount' => 350],
                ['resource_name' => 'Hydrogenium', 'amount' => 400],
                ['resource_name' => 'Kyberkristall', 'amount' => 100],
            ],
        ],
        [
            'name' => 'Laboratory',
            'details_id' => 3,
            'effect_value' => 3,
            'build_time' => 60,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 300],
                ['resource_name' => 'Titanium', 'amount' => 250],
                ['resource_name' => 'Hydrogenium', 'amount' => 425],
                ['resource_name' => 'Kyberkristall', 'amount' => 375],
            ],
        ],
        [
            'name' => 'Warehouse',
            'details_id' => 4,
            'effect_value' => 1_500,
            'build_time' => 60,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 400],
                ['resource_name' => 'Titanium', 'amount' => 300],
                ['resource_name' => 'Hydrogenium', 'amount' => 200],
                ['resource_name' => 'Kyberkristall', 'amount' => 150],
            ],
        ],
        [
            'name' => 'Scanner',
            'details_id' => 6,
            'effect_value' => 4000,
            'build_time' => 60,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 200],
                ['resource_name' => 'Titanium', 'amount' => 250],
                ['resource_name' => 'Hydrogenium', 'amount' => 250],
                ['resource_name' => 'Kyberkristall', 'amount' => 300],
            ],
        ],
        [
            'name' => 'Shield',
            'details_id' => 8,
            'effect_value' => 1,
            'build_time' => 60,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 300],
                ['resource_name' => 'Titanium', 'amount' => 300],
                ['resource_name' => 'Hydrogenium', 'amount' => 400],
                ['resource_name' => 'Kyberkristall', 'amount' => 475],
            ],
        ],
        /*         [
                    'name' => 'Market',
                    'details_id' => 5,
                    'effect_value' => 200,
                    'build_time' => 60,
                    'is_active' => true,
                    'level' => 1,
                    'costs' => [
                        ['resource_name' => 'Carbon', 'amount' => 100],
                        ['resource_name' => 'Titanium', 'amount' => 100],
                        ['resource_name' => 'Hydrogenium', 'amount' => 100],
                        ['resource_name' => 'Kyberkristall', 'amount' => 100],
                    ],
                ], */
        /*        [
                    'name' => 'Supply',
                    'details_id' => 7,
                    'effect_value' => 20,
                    'build_time' => 60,
                    'is_active' => false,
                    'level' => 1,
                    'costs' => [
                        ['resource_name' => 'Carbon', 'amount' => 100],
                        ['resource_name' => 'Titanium', 'amount' => 100],
                        ['resource_name' => 'Hydrogenium', 'amount' => 100],
                        ['resource_name' => 'Kyberkristall', 'amount' => 100],
                    ],
                ], */
        /*         [
                    'name' => 'Energy',
                    'details_id' => 9,
                    'effect_value' => 20,
                    'build_time' => 60,
                    'is_active' => false,
                    'level' => 1,
                    'costs' => [
                        ['resource_name' => 'Carbon', 'amount' => 100],
                        ['resource_name' => 'Titanium', 'amount' => 100],
                        ['resource_name' => 'Hydrogenium', 'amount' => 100],
                        ['resource_name' => 'Kyberkristall', 'amount' => 100],
                    ],
                ], */
    ],
];
