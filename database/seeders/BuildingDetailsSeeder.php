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
        BuildingDetails::truncate();

        BuildingDetails::create([
            'name' => 'Shipyard',
            'description' => 'The higher the shipyard level, the faster spaceships are made.',
            'image' => '/storage/buildings/shipyard_sm.webp',
            'effect' => 'Production speed',
        ]);

        BuildingDetails::create([
            'name' => 'Hangar',
            'description' => 'The higher the hangar level, the more spaceships can be stored.',
            'image' => '/storage/buildings/hangar_sm.webp',
            'effect' => 'Crew Limit',
        ]);

        BuildingDetails::create([
            'name' => 'Laboratory',
            'description' => 'The higher the laboratory level, the better spaceships can be produced.',
            'image' => '/storage/buildings/laboratory_sm.webp',
            'effect' => 'Research Points',
        ]);

        BuildingDetails::create([
            'name' => 'Warehouse',
            'description' => 'The higher the Warehouse level, the more resources can be stored.',
            'image' => '/storage/buildings/warehouse_sm.webp',
            'effect' => 'Resource storage',
        ]);

        BuildingDetails::create([
            'name' => 'Market',
            'description' => 'The higher the market level, the more resources can be traded.',
            'image' => '/storage/buildings/market_sm.webp',
            'effect' => 'Trade Limit',
        ]);

        BuildingDetails::create([
            'name' => 'Scanner',
            'description' => 'The higher the Scanner level, the wider the scanning range.',
            'image' => '/storage/buildings/scanner.webp',
            'effect' => 'Scanner range',
        ]);

        BuildingDetails::create([
            'name' => 'Supply',
            'description' => 'The higher the supply level, the more crew can be supplied.',
            'image' => '/storage/buildings/supply_sm.jpg',
            'effect' => 'Supply capacity',
        ]);

        BuildingDetails::create([
            'name' => 'Shield',
            'description' => 'The higher the energy shield level, the more attackers are blocked.',
            'image' => '/storage/buildings/shield_sm.webp',
            'effect' => 'Defense',
        ]);

        BuildingDetails::create([
            'name' => 'Energy',
            'description' => 'The higher the Energy Modul level, the more energy will be produced',
            'image' => '/storage/buildings/energy_sm.webp',
            'effect' => 'Energy',
        ]);
    }
}
