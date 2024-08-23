<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BuildingDetails;

class BuildingDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BuildingDetails::create([
            'name' => 'Shipyard',
            'description' => 'The higher the shipyard level, the faster spaceships are made.',
            'image' => 'shipyard.png',
            'effect' => 'increases production speed of spacecrafts',
        ]);

        BuildingDetails::create([
            'name' => 'Hangar',
            'description' => 'The higher the hangar level, the more spaceships can be stored.',
            'image' => 'hangar.png',
            'effect' => 'increases unit Limit',
        ]);

        BuildingDetails::create([
            'name' => 'Laboratory',
            'description' => 'The higher the laboratory level, the better spaceships can be produced.',
            'image' => 'laboratory.png',
            'effect' => '',
        ]);

        BuildingDetails::create([
            'name' => 'Warehouse',
            'description' => 'The higher the Warehouse level, the more resources can be stored.',
            'image' => 'warehouse.png',
            'effect' => '',
        ]);

        BuildingDetails::create([
            'name' => 'Marketplace',
            'description' => 'The higher the marketplace level, the more resources can be traded.',
            'image' => 'marketplace.png',
            'effect' => '',
        ]);

        BuildingDetails::create([
            'name' => 'Scanner',
            'description' => 'The higher the Sector Scanner level, the more sectors will be scanned',
            'image' => 'scanner.png',
            'effect' => '',
        ]);

        BuildingDetails::create([
            'name' => 'Supply',
            'description' => 'The higher the supply level, the more crew can be supplied.',
            'image' => 'supply.png',
            'effect' => '',
        ]);

        BuildingDetails::create([
            'name' => 'Shield',
            'description' => 'The higher the energy shield level, the more attackers are blocked.',
            'image' => 'shield.png',
            'effect' => '',
        ]);

        BuildingDetails::create([
            'name' => 'Energy',
            'description' => 'The higher the Energy Modul level, the more energy will be produced',
            'image' => 'energy.png',
            'effect' => '',
        ]);
    }
}
