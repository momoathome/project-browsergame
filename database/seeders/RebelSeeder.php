<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Rebel\Services\SetupInitialRebel;

class RebelSeeder extends Seeder
{
    protected $rebelService;

    public function __construct(SetupInitialRebel $setupInitialRebel)
    {
        $this->rebelService = $setupInitialRebel;
    } 
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $factions = [
            /* Rostrot / Orange */
            'Rostwölfe' => [
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
            /* Lila / Violett */
            'Kult der Leere' => [
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
            /* Blau / Blutrot */
            'Sternenplünderer' => [
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
            /* Grün / Gelb */
            'Gravbrecher' => [
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
        ];


        foreach ($factions as $factionName => $leaders) {
            foreach ($leaders as $leader) {
                $this->rebelService->create($leader, $factionName);
            }
        }

    }
}
