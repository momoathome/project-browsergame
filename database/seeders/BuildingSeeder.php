<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\BuildingDetails;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shipyarddetailsId = BuildingDetails::where('name', 'Shipyard')->first()->id;
        $hangardetailsId = BuildingDetails::where('name', 'Hangar')->first()->id;
        $laboratorydetailsId = BuildingDetails::where('name', 'Laboratory')->first()->id;
        $warehousedetailsId = BuildingDetails::where('name', 'Warehouse')->first()->id;
        $marketplacedetailsId = BuildingDetails::where('name', 'Marketplace')->first()->id;
        $scannerdetailsId = BuildingDetails::where('name', 'Scanner')->first()->id;
        $supplydetailsId = BuildingDetails::where('name', 'Supply')->first()->id;
        $shielddetailsId = BuildingDetails::where('name', 'Shield')->first()->id;
        $energydetailsId = BuildingDetails::where('name', 'Energy')->first()->id;

        Building::create([
            'user_id' => 1,
            'details_id' => $shipyarddetailsId,
            'effect_value' => 10,
            'level' => 1,
            'buildTime' => 900, // In Seconds
            'cost' => 2_000_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $hangardetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 720, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $laboratorydetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 1800, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $warehousedetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 900, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $marketplacedetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 600, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $scannerdetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 500, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $supplydetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 300, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $shielddetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 1500, // In Seconds
            'cost' => 1_500_000,
        ]);

        Building::create([
            'user_id' => 1,
            'details_id' => $energydetailsId,
            'effect_value' => 20,
            'level' => 1,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
        ]);
    }
}
