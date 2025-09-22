<?php

return [
    'buildings' => [
        [
            'name' => 'Core',
            'details_id' => 1,
            'effect_value' => 1,
            'build_time' => 330,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 125],
                ['resource_name' => 'Titanium', 'amount' => 175],
                ['resource_name' => 'Hydrogenium', 'amount' => 225],
                ['resource_name' => 'Kyberkristall', 'amount' => 200],
            ],
        ],
        [
            'name' => 'Shipyard',
            'details_id' => 2,
            'effect_value' => 1, // Basiswert für Level 1
            'build_time' => 300,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 150],
                ['resource_name' => 'Titanium', 'amount' => 175],
                ['resource_name' => 'Hydrogenium', 'amount' => 125],
                ['resource_name' => 'Kyberkristall', 'amount' => 125],
            ],
        ],
        [
            'name' => 'Hangar',
            'details_id' => 3,
            'effect_value' => 10,
            'build_time' => 315,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 175],
                ['resource_name' => 'Titanium', 'amount' => 200],
                ['resource_name' => 'Hydrogenium', 'amount' => 175],
                ['resource_name' => 'Kyberkristall', 'amount' => 150],
            ],
        ],
        [
            'name' => 'Laboratory',
            'details_id' => 4,
            'effect_value' => 3,
            'build_time' => 275,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 150],
                ['resource_name' => 'Titanium', 'amount' => 175],
                ['resource_name' => 'Hydrogenium', 'amount' => 175],
                ['resource_name' => 'Kyberkristall', 'amount' => 150],
            ],
        ],
        [
            'name' => 'Warehouse',
            'details_id' => 5,
            'effect_value' => 1_500,
            'build_time' => 260,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 175],
                ['resource_name' => 'Titanium', 'amount' => 200],
                ['resource_name' => 'Hydrogenium', 'amount' => 100],
                ['resource_name' => 'Kyberkristall', 'amount' => 125],
            ],
        ],
        [
            'name' => 'Scanner',
            'details_id' => 6,
            'effect_value' => 4000,
            'build_time' => 290,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 125],
                ['resource_name' => 'Titanium', 'amount' => 150],
                ['resource_name' => 'Hydrogenium', 'amount' => 150],
                ['resource_name' => 'Kyberkristall', 'amount' => 200],
            ],
        ],
        [
            'name' => 'Guardian',
            'details_id' => 7,
            'effect_value' => 1,
            'build_time' => 300,
            'is_active' => true,
            'level' => 1,
            'costs' => [
                ['resource_name' => 'Carbon', 'amount' => 150],
                ['resource_name' => 'Titanium', 'amount' => 150],
                ['resource_name' => 'Hydrogenium', 'amount' => 200],
                ['resource_name' => 'Kyberkristall', 'amount' => 200],
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
