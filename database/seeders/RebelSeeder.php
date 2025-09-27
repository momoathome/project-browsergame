<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Rebel\Services\SetupInitialRebel;

class RebelSeeder extends Seeder
{

    protected SetupInitialRebel $rebelservice;

    public function __construct(SetupInitialRebel $rebelservice)
    {
        $this->rebelservice = $rebelservice;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $config = config('game.rebels');

        foreach ($config['factions'] as $faction => $data) {
            foreach ($data['leaders'] as $name) {
                $weights = [1 => 0.4, 2 => 0.3, 3 => 0.15, 4 => 0.1, 5 => 0.05];
                $rand = mt_rand() / mt_getrandmax();
                $sum = 0;
                foreach ($weights as $level => $weight) {
                    $sum += $weight;
                    if ($rand <= $sum) {
                        $difficulty = $level;
                        break;
                    }
                }
                $fleetCap = 10;       // Basiswert
                $resourceCap = 1000;  // Basiswert


                $this->rebelservice->create([
                    'name'              => $name,
                    'faction'           => $faction,
                    'difficulty_level'   => $difficulty,
                    'last_interaction'  => now(),
                    'defeated_count'    => 0,
                    'fleet_cap'          => $fleetCap,
                    'resource_cap'      => $resourceCap,
                    'adaptation_level'  => 0,
                    'behavior'          => $data['base_behavior'],
                    'base_chance'       => $data['base_chance'],
                ]);
            }
        }
    }
}
