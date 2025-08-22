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
            'description' => 'Increases spaceship production speed.',
            'image' => '/images/buildings/shipyard.webp',
            'effect' => 'Production speed',
        ]);

        BuildingDetails::create([
            'name' => 'Hangar',
            'description' => 'Increases crew limit.',
            'image' => '/images/buildings/hangar.webp',
            'effect' => 'Crew Limit',
        ]);

        BuildingDetails::create([
            'name' => 'Laboratory',
            'description' => 'Increases research points count.',
            'image' => '/images/buildings/laboratory.webp',
            'effect' => 'Research Points',
        ]);

        BuildingDetails::create([
            'name' => 'Warehouse',
            'description' => 'Increases resource storage capacity.',
            'image' => '/images/buildings/warehouse.webp',
            'effect' => 'Resource storage',
        ]);

        BuildingDetails::create([
            'name' => 'Market',
            'description' => 'Increases trade limit for resources.',
            'image' => '/images/buildings/market.webp',
            'effect' => 'Trade Limit',
        ]);

        BuildingDetails::create([
            'name' => 'Scanner',
            'description' => 'Increases scanner range.',
            'image' => '/images/buildings/scanner.webp',
            'effect' => 'Scanner range',
        ]);

        BuildingDetails::create([
            'name' => 'Supply',
            'description' => 'Increases supply capacity for crew.',
            'image' => '/images/buildings/supply.jpg',
            'effect' => 'Supply capacity',
        ]);

        BuildingDetails::create([
            'name' => 'Shield',
            'description' => 'Increases defense against attackers.',
            'image' => '/images/buildings/shield.webp',
            'effect' => 'Defense',
        ]);

        BuildingDetails::create([
            'name' => 'Energy',
            'description' => 'Increases energy production.',
            'image' => '/images/buildings/energy.webp',
            'effect' => 'Energy',
        ]);
    }
}
