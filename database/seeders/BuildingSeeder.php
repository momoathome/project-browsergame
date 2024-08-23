<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\BuildingResourceCost;
use App\Models\Resource;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = Resource::pluck('id', 'name')->toArray();

        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $buildingDetails = [
                'Shipyard' => $this->createBuilding($userId, 1, 10, 900),
                'Hangar' => $this->createBuilding($userId, 2, 20, 720),
                'Laboratory' => $this->createBuilding($userId, 3, 20, 1800),
                'Warehouse' => $this->createBuilding($userId, 4, 20, 900),
                'Marketplace' => $this->createBuilding($userId, 5, 20, 600),
                'Scanner' => $this->createBuilding($userId, 6, 20, 500),
                'Supply' => $this->createBuilding($userId, 7, 20, 300),
                'Shield' => $this->createBuilding($userId, 8, 20, 1500),
                'Energy' => $this->createBuilding($userId, 9, 20, 1200),
            ];
    
            foreach ($buildingDetails as $name => $building) {
                $resourceCosts = $this->getResourceCostsForBuilding($name, $resources);
    
                foreach ($resourceCosts as $resourceCost) {
                    BuildingResourceCost::create([
                        'building_id' => $building->id,
                        'resource_id' => $resourceCost['resource_id'],
                        'amount' => $resourceCost['amount'],
                    ]);
                }
            }
        }
    }

    private function createBuilding($userId, $detailsId, $effectValue, $buildTime)
    {
        return Building::create([
            'user_id' => $userId,
            'level' => 1,
            'details_id' => $detailsId,
            'effect_value' => $effectValue,
            'buildTime' => $buildTime,
        ]);
    }

    private function getResourceCostsForBuilding($buildingName, $resources)
    {
        // Beispielhafte Ressourcenkosten für jedes Gebäude
        $costs = [
            'Shipyard' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Hangar' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Laboratory' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Warehouse' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Marketplace' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Scanner' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Supply' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Shield' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
            'Energy' => [
                ['resource_id' => $resources['Carbon'], 'amount' => 100],
                ['resource_id' => $resources['Titanium'], 'amount' => 100],
                ['resource_id' => $resources['Hydrogenium'], 'amount' => 100],
                ['resource_id' => $resources['Kyberkristall'], 'amount' => 100],
            ],
        ];

        return $costs[$buildingName] ?? [];
    }
}
