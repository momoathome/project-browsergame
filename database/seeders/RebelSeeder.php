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
                $difficulty = rand(1, 5);

                $fleetCap = 1000 * $difficulty;
                $growthRate = 1 + ($difficulty * 0.1);
                $lootMultiplier = 1 + ($difficulty * 0.05);

                $this->rebelservice->create([
                    'name'              => $name,
                    'faction'           => $faction,
                    'difficulty_level'   => $difficulty,
                    'last_interaction'  => now(),
                    'defeated_count'    => 0,
                    'fleet_cap'          => $fleetCap,
                    'fleet_growth_rate'  => $growthRate,
                    'loot_multiplier'   => $lootMultiplier,
                    'adaptation_level'  => 0,
                    'behavior'          => $data['base_behavior'],
                    'base_chance'       => $data['base_chance'],
                ]);
            }
        }
    }
}
