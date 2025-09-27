<?php

return [

    'resources_per_tick' => 10,         // Basisrate der Ressourcengenerierung
    'tick_interval_minutes' => 10,      // Minuten pro Tick
    'spacecraft_produce_speed' => 1.0,
    'spacecraft_flight_speed' => 1.0,

    /*
    |--------------------------------------------------------------------------
    | Faction Definitions
    |--------------------------------------------------------------------------
    | Hier sind alle Fraktionen mit Leadern, Basis-Verhalten und Basis-Chance
    | definiert. Diese Datei kann jederzeit erweitert werden.
    */

    'factions' => [
        'Rostwölfe' => [
            'leaders' => [
                'Korga Eisenklaue',
                'Brakk der Rostfürst',
                'Maela Ascheherz',
                'Vorn Schredderfaust',
                'Rax die Staubkönigin',
                'Zarok der Zerstörer',
                'Thalor der Unermüdliche',
                'Gorak der Eisenbrecher',
                'Trax der Schlächter',
                'Kyra die Blutjägerin',
            ],
            'base_behavior' => 'very aggressive',
            'base_chance'   => 0.3,
        ],
        'Kult der Leere' => [
            'leaders' => [
                'Hohepriester Varuun',
                'Eira die Leerenmutter',
                'Archon Zeyth',
                'Malgor Sternenlos',
                'Xyra die Nachtflamme',
                'Draxus der Schattenfürst',
                'Liora die Seelenbinderin',
                'Vexis der Dunkelwanderer',
                'Lyra die Sternenruferin',
                'Zorin der Leerenlord',
            ],
            'base_behavior' => 'defensive',
            'base_chance'   => 0.15,
        ],
        'Sternenplünderer' => [
            'leaders' => [
                'Kael Blutklinge',
                'Sira Schattenfalke',
                'Torven Rotklaue',
                'Jax Kometschrecken',
                'Riana Flammenherz',
                'Doran der Weltraumräuber',
                'Luna die Sternenjägerin',
                'Zane der Galaktische',
                'Mira die Kosmosdiebin',
                'Vex der Raumfahrer',
            ],
            'base_behavior' => 'balanced',
            'base_chance'   => 0.2,
        ],
        'Gravbrecher' => [
            'leaders' => [
                'Lyras Redshift',
                'Kaelen Fluxklinge',
                'Torga Tachyonfaust',
                'Veyra Schattenlauf',
                'Dren Warpreißer',
                'Zyra Gravbrecherin',
                'Orin der Raumzerstörer',
                'Mira die Sternenspringerin',
                'Tachion der Lichtbrecher',
                'Xyra die Gravitationsmeisterin',
            ],
            'base_behavior' => 'aggressive',
            'base_chance'   => 0.25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Behavior Definitions
    |--------------------------------------------------------------------------
    | Hier sind alle Basis-Verhaltensweisen definiert. Diese werden von den
    | Fraktionen genutzt, um Kampf- und KI-Entscheidungen zu bestimmen.
    */

    'behaviors' => [
        'very defensive' => [
            'attack_multiplier' => 0.5,
            'fleet_bias' => [
                'defense' => 0.9,
                'attack'  => 0.1
            ]
        ],
        'defensive' => [
            'attack_multiplier' => 0.75,
            'fleet_bias' => [
                'defense' => 0.7,
                'attack'  => 0.3
            ]
        ],
        'balanced' => [
            'attack_multiplier' => 1.0,
            'fleet_bias' => [
                'defense' => 0.5,
                'attack'  => 0.5
            ]
        ],
        'aggressive' => [
            'attack_multiplier' => 1.25,
            'fleet_bias' => [
                'defense' => 0.3,
                'attack'  => 0.7
            ]
        ],
        'very aggressive' => [
            'attack_multiplier' => 1.5,
            'fleet_bias' => [
                'defense' => 0.1,
                'attack'  => 0.9
            ]
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | faction spacecrafts Definitions
    |--------------------------------------------------------------------------
    | Hier sind Raumschiffe für die Fraktionen definiert pro Phase.
    |
    | Rostwölfe: Nutzen viele kleine, billige Schiffe (Swarms). Merlin, Comet, Javelin, Ares
    | Kult der Leere: Nutzen Wenige, aber robuste Schiffe (Tank-orientiert). Sentinel, Probe, Nova
    | Sternenplünderer: Nutzen ausgewogene Schiffe. Javelin, Sentinel, Probe, Ares, Nova 
    | Gravbrecher: Nutzen schnelle und vielseitige Schiffe. Merlin, Comet, Javelin, Probe
    */

    'faction_spacecrafts' => [
        'Rostwölfe' => [
            'early' => ['Merlin', 'Comet'],
            'mid'   => ['Merlin', 'Comet', 'Javelin'],
            'late'  => ['Merlin', 'Comet', 'Javelin', 'Ares'],
        ],
        'Kult der Leere' => [
            'early' => ['Merlin', 'Comet', 'Sentinel'],
            'mid'   => ['Merlin', 'Comet', 'Sentinel', 'Probe'],
            'late'  => ['Merlin', 'Comet', 'Sentinel', 'Probe', 'Nova'],
        ],
        'Sternenplünderer' => [
            'early' => ['Merlin', 'Comet', 'Sentinel'],
            'mid'   => ['Comet', 'Javelin', 'Sentinel', 'Probe'],
            'late'  => ['Comet', 'Javelin', 'Sentinel', 'Probe', 'Ares', 'Nova'],
        ],
        'Gravbrecher' => [
            'early' => ['Merlin', 'Comet'],
            'mid'   => ['Merlin', 'Comet', 'Javelin'],
            'late'  => ['Comet', 'Javelin', 'Sentinel', 'Probe'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Definitions
    |--------------------------------------------------------------------------
    | Hier sind alle Ressourcen-Verhältnisse für die Fraktionen definiert.
    |
    | Diese Verhältnisse bestimmen, wie die Ressourcen generiert und genutzt werden.
    | Rostwölfe: Nutzen viele kleine, billige Schiffe (Swarms). Merlin, Comet, Javelin, Ares
    | Kult der Leere: Nutzen Wenige, aber robuste Schiffe (Tank-orientiert). Sentinel, Probe, Nova
    | Sternenplünderer: Nutzen ausgewogene Schiffe. Javelin, Sentinel, Probe, Ares, Nova 
    | Gravbrecher: Nutzen schnelle und vielseitige Schiffe. Merlin, Comet, Javelin, Probe
    */

    'resource_ratios' => [
        'Rostwölfe' => [
            'Carbon'      => 0.75,
            'Titanium'    => 0.85,
            'Hydrogenium' => 0.5,
            'Kyberkristall' => 0.4,
            'Cobalt'        => 0.15,
            'Iridium'       => 0.01,
            'Uraninite'     => 0.01,
            'Thorium'       => 0.01,
        ],
        'Kult der Leere' => [
            'Carbon'      => 0.8,
            'Titanium'    => 0.8,
            'Hydrogenium' => 0.7,
            'Kyberkristall' => 0.75,
            'Cobalt'        => 0.2,
            'Iridium'       => 0.025,
            'Uraninite'     => 0.025,
            'Thorium'       => 0.02,
            'Astatine'      => 0.02,
        ],
        'Sternenplünderer' => [
            'Carbon'      => 0.9,
            'Titanium'    => 0.9,
            'Hydrogenium' => 0.8,
            'Kyberkristall' => 0.65,
            'Cobalt'        => 0.15,
            'Iridium'       => 0.05,
            'Uraninite'     => 0.025,
            'Thorium'       => 0.02,
            'Astatine'      => 0.02,
        ],
        'Gravbrecher' => [
            'Carbon'      => 0.6,
            'Titanium'    => 0.6,
            'Hydrogenium' => 0.5,
            'Kyberkristall' => 0.4,
            'Cobalt'        => 0.1,
            'Iridium'       => 0.075,
            'Uraninite'     => 0.025,
            'Thorium'       => 0.015,
        ],
    ],


];


/* 
function calculateAttackChance($baseChance, $behavior, $behaviors) {
    $multiplier = $behaviors[$behavior]['attack_multiplier'];
    return min($baseChance * $multiplier, 1.0);
}

function getFleetBias($behavior, $behaviors) {
    return $behaviors[$behavior]['fleet_bias'];
}

$base_attack_chance = 0.6; // 60%

$behavior = 'aggressive';

$attackChance = calculateAttackChance($base_attack_chance, $behavior, $behaviors);
$fleetBias = getFleetBias($behavior, $behaviors);

echo "Behavior: $behavior\n";
echo "Attack Chance: " . ($attackChance * 100) . "%\n";
echo "Fleet Bias: Attack " . ($fleetBias['attack'] * 100) . "% / Defense " . ($fleetBias['defense'] * 100) . "%\n";

Behavior: aggressive
Attack Chance: 75%
Fleet Bias: Attack 60% / Defense 40%


function createFleetComposition($fleetBias, $totalShips) {
    return [
        'attack_ships' => round($fleetBias['attack'] * $totalShips),
        'defense_ships' => round($fleetBias['defense'] * $totalShips),
    ];
}

$fleet = createFleetComposition($fleetBias, 100);
print_r($fleet);

Array
(
    [attack_ships] => 60
    [defense_ships] => 40
)


*/
