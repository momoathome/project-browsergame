<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Spacecraft;
use App\Models\SpacecraftDetails;
use App\Models\SpacecraftResourceCost;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;

class SpacecraftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('spacecrafts')->truncate();
        DB::table('spacecraft_resource_costs')->truncate();

        $resources = Resource::pluck('id', 'name')->toArray();

        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $spacecraftDetails = [
                'Merlin' => $this->createSpacecraft($userId, 'Merlin', 10, 1, 10, 1, 900),
                'Comet' => $this->createSpacecraft($userId, 'Comet', 20, 1, 10, 1, 720),
                'Javelin' => $this->createSpacecraft($userId, 'Javelin', 20, 1, 10, 1, 1800),
                'Sentinel' => $this->createSpacecraft($userId, 'Sentinel', 20, 1, 10, 1, 900),
                'Probe' => $this->createSpacecraft($userId, 'Probe', 20, 1, 10, 1, 600),
                'Ares' => $this->createSpacecraft($userId, 'Ares', 20, 1, 10, 1, 500),
                'Nova' => $this->createSpacecraft($userId, 'Nova', 20, 1, 10, 1, 300),
                'Horus' => $this->createSpacecraft($userId, 'Horus', 20, 1, 10, 1, 1500),
                'Reaper' => $this->createSpacecraft($userId, 'Reaper', 20, 1, 10, 1, 1200),
                'Mole' => $this->createSpacecraft($userId, 'Mole', 20, 1, 10, 1, 1200),
                'Titan' => $this->createSpacecraft($userId, 'Titan', 20, 1, 10, 1, 1200),
                'Nomad' => $this->createSpacecraft($userId, 'Nomad', 20, 1, 10, 1, 1200),
                'Hercules' => $this->createSpacecraft($userId, 'Hercules', 20, 1, 10, 1, 1200),
                'Collector' => $this->createSpacecraft($userId, 'Collector', 20, 1, 10, 1, 1200),
                'Reclaimer' => $this->createSpacecraft($userId, 'Reclaimer', 20, 1, 10, 1, 1200),
            ];

            foreach ($spacecraftDetails as $name => $spacecraft) {
                $resourceCosts = $this->getResourceCostsForSpacecraft($name, $resources);

                foreach ($resourceCosts as $resourceCost) {
                    SpacecraftResourceCost::create([
                        'spacecraft_id' => $spacecraft->id,
                        'resource_id' => $resourceCost['resource_id'],
                        'amount' => $resourceCost['amount'],
                    ]);
                }
            }
        }

    }

    private function createSpacecraft($userId, $spacecraftName, $combat, $count, $cargo, $unitLimit, $buildTime)
    {
        $detailsId = SpacecraftDetails::where('name', $spacecraftName)->first()->id;

        return Spacecraft::create([
            'user_id' => $userId,
            'details_id' => $detailsId,
            'combat' => $combat,
            'count' => $count,
            'cargo' => $cargo,
            'unitLimit' => $unitLimit,
            'buildTime' => $buildTime,
            'unlocked' => false,
        ]);
    }

    private function getResourceCostsForSpacecraft($name, $resources)
    {
        // Beispiel für die Kostenberechnung. Passen Sie dies an Ihre Logik an.
        $costMapping = [
            'Merlin' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Comet' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Javelin' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Sentinel' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Probe' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Ares' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Nova' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Horus' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Reaper' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Mole' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Titan' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Nomad' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Hercules' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Collector' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Reclaimer' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
        ];

        return $costMapping[$name] ?? [];
    }

}
