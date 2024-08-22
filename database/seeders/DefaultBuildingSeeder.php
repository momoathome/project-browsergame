<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\BuildingSchema;

class DefaultBuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shipyardSchemaId = BuildingSchema::where('name', 'Shipyard')->first()->id;
        $hangarSchemaId = BuildingSchema::where('name', 'Hangar')->first()->id;

        Building::create([
            'user_id' => 1,
            'schema_id' => $shipyardSchemaId,
            'effect_value' => 10,
            'level' => 1,
            'buildTime' => 900, // In Seconds
            'cost' => 2_000_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $hangarSchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 720, // In Seconds
            'cost' => 1_500_000,
        ]);
    }
}
