<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Building\Models\BuildingDetails;

class BuildingDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BuildingDetails::create([
            'name' => 'Core',
            'description' => 'Powers station, unlocks building slots and boosts upgrade speed.',
            'image' => '/images/buildings/core.webp',
            'effect' => 'Upgrade speed',
        ]);

        BuildingDetails::create([
            'name' => 'Shipyard',
            'description' => 'Builds spacecrafts, unlocks stronger types and production slots.',
            'image' => '/images/buildings/shipyard.webp',
            'effect' => 'Production speed',
        ]);

        BuildingDetails::create([
            'name' => 'Hangar',
            'description' => 'Stores your fleet, repairs them and adds docking slots.',
            'image' => '/images/buildings/hangar.webp',
            'effect' => 'Crew Limit',
        ]);

        BuildingDetails::create([
            'name' => 'Laboratory',
            'description' => 'Generates research points and unlocks new tech.',
            'image' => '/images/buildings/laboratory.webp',
            'effect' => 'Research Points',
        ]);

        BuildingDetails::create([
            'name' => 'Warehouse',
            'description' => 'Stores ressources and shields them from looting.',
            'image' => '/images/buildings/warehouse.webp',
            'effect' => 'Resource storage',
        ]);

        BuildingDetails::create([
            'name' => 'Scanner',
            'description' => 'Extends scanner range and unlocks scan modes.',
            'image' => '/images/buildings/scanner.webp',
            'effect' => 'Scanner range',
        ]);

/*         BuildingDetails::create([
            'name' => 'Market',
            'description' => 'Increases trade limit for resources.',
            'image' => '/images/buildings/market.webp',
            'effect' => 'Trade Limit',
        ]); */

  /*       BuildingDetails::create([
            'name' => 'Supply',
            'description' => 'Increases supply capacity for crew.',
            'image' => '/images/buildings/supply.jpg',
            'effect' => 'Supply capacity',
        ]); */

        BuildingDetails::create([
            'name' => 'Guardian',
            'description' => 'Deploys defense mechanisms to defend your station.',
            'image' => '/images/buildings/Guardian.webp',
            'effect' => 'Defense',
        ]);

/*         BuildingDetails::create([
            'name' => 'Energy',
            'description' => 'Increases energy production.',
            'image' => '/images/buildings/energy.webp',
            'effect' => 'Energy',
        ]); */
    }
}
