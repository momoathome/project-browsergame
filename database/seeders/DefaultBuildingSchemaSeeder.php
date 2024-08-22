<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BuildingSchema;

class DefaultBuildingSchemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BuildingSchema::create([
            'name' => 'Shipyard',
            'description' => 'The higher the shipyard level, the faster spaceships are made.',
            'image' => 'shipyard.png',
            'effect' => 'increases production speed of spacecrafts',
        ]);

        BuildingSchema::create([
            'name' => 'Hangar',
            'description' => 'The higher the hangar level, the more spaceships can be stored.',
            'image' => 'hangar.png',
            'effect' => 'increases unit Limit',
        ]);
    }
}
