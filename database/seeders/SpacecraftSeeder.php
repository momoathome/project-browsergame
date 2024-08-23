<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Spacecraft;
use App\Models\SpacecraftDetails;

class SpacecraftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merlinDetailsId = SpacecraftDetails::where('name', 'Merlin')->first()->id;
        $cometDetailsId = SpacecraftDetails::where('name', 'Comet')->first()->id;
        $javelinDetailsId = SpacecraftDetails::where('name', 'Javelin')->first()->id;
        $sentinelDetailsId = SpacecraftDetails::where('name', 'Sentinel')->first()->id;
        $probeDetailsId = SpacecraftDetails::where('name', 'Probe')->first()->id;
        $aresDetailsId = SpacecraftDetails::where('name', 'Ares')->first()->id;
        $novaDetailsId = SpacecraftDetails::where('name', 'Nova')->first()->id;
        $horusDetailsId = SpacecraftDetails::where('name', 'Horus')->first()->id;
        $reaperDetailsId = SpacecraftDetails::where('name', 'Reaper')->first()->id;
        $moleDetailsId = SpacecraftDetails::where('name', 'Mole')->first()->id;
        $titanDetailsId = SpacecraftDetails::where('name', 'Titan')->first()->id;
        $nomadDetailsId = SpacecraftDetails::where('name', 'Nomad')->first()->id;
        $herculesDetailsId = SpacecraftDetails::where('name', 'Hercules')->first()->id;
        $collectorDetailsId = SpacecraftDetails::where('name', 'Collector')->first()->id;
        $reclaimerDetailsId = SpacecraftDetails::where('name', 'Reclaimer')->first()->id;

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $merlinDetailsId,
            'combat' => 10,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 900, // In Seconds
            'cost' => 2_000_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $cometDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 720, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $javelinDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1800, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $sentinelDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 900, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $probeDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 600, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $aresDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 500, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $novaDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 300, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $horusDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1500, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $reaperDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $moleDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $titanDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $nomadDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $herculesDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $collectorDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);

        Spacecraft::create([
            'user_id' => 1,
            'details_id' => $reclaimerDetailsId,
            'combat' => 20,
            'count' => 1,
            'cargo' => 10,
            'buildTime' => 1200, // In Seconds
            'cost' => 1_500_000,
            'unitLimit' => 8,
            'unlocked' => false
        ]);
    }
}
