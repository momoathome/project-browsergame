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
        $names = [
            'Blutklingen-Klan',
            'Schwarze Schwingen',
            'Kult der Leere',
            'Die Rostwölfe',
            'Stahlvipern',
            'Aschengeborene',
            'Splitterflotte',
            'Die Staubgeißel',
            'Kometenräuber',
            'Schattenpakt',
            'Die Brandnarben',
            'Nova-Hyänen',
            'Die Eisenzähne',
            'Sternenplünderer',
            'Die Leerenkinder',
            'Falken des Chaos',
            'Die Redshift-Räuber',
            'Verlorene Legion',
            'Die Gravbrecher',
            'Phantom-Syndikat',
            'Die Vergessenen',  
            'Skarn-Piraten',
            'Kult der schwarzen Sonne',
            'Die Warpgezeichneten',
            'Orbit-Schakale',
            'Die Schattenflotte',
            'Die Nebelkrieger',
            'Die Sternenwanderer',
            'Orion Syndikat',
            'Die Lichtbringer'
        ];

        foreach ($names as $idx => $name) {
            $this->rebelService->create($idx + 1, $name);
        }
    }
}
