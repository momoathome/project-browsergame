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
        $laboratorySchemaId = BuildingSchema::where('name', 'Laboratory')->first()->id;
        $warehouseSchemaId = BuildingSchema::where('name', 'Warehouse')->first()->id;
        $marketplaceSchemaId = BuildingSchema::where('name', 'Marketplace')->first()->id;
        $scannerSchemaId = BuildingSchema::where('name', 'Scanner')->first()->id;
        $supplySchemaId = BuildingSchema::where('name', 'Supply')->first()->id;
        $shieldSchemaId = BuildingSchema::where('name', 'Shield')->first()->id;
        $energySchemaId = BuildingSchema::where('name', 'Energy')->first()->id;

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

        Building::create([
            'user_id' => 1,
            'schema_id' => $laboratorySchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 1800, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $warehouseSchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 900, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $marketplaceSchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 600, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $scannerSchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 500, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $supplySchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 300, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $shieldSchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 1500, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'schema_id' => $energySchemaId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
        ]);
    }
}
