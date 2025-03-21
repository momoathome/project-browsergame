<?php

namespace Orion\Modules\Building\Services;

use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Models\BuildingResourceCost;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Building\Services\BuildingCostCalculator;

class BuildingUpgradeService
{
    public function __construct(
        private readonly BuildingCostCalculator $costCalculator
    ) {
    }

    public function calculateUpgradeCosts(Building $building)
    {
        return $this->costCalculator->calculateUpgradeCost($building);
    }

    public function upgradeBuilding(Building $building)
    {
        $costs = $this->calculateUpgradeCosts($building);

        $building->level += 1;
        $building->effect_value = $this->calculateNewEffectValue($building);
        $building->build_time = $this->calculateBuildTime($building);
        $building->save();

        $this->updateBuildingCosts($building, $costs);

        return $building;
    }

    private function calculateNewEffectValue(Building $building)
    {
        // Hier die Logik für Effektverbesserung je nach Gebäudetyp
        // Dies ist nur ein einfaches Beispiel
        switch ($building->details->name) {
            case 'Shipyard':
                return 1.1 + ($building->level - 1) * 0.05;
            case 'Hangar':
                return 10 + ($building->level - 1) * 10;
            // Weitere Gebäude...
            default:
                return $building->effect_value * 1.1;
        }
    }

    private function calculateBuildTime(Building $building)
    {
        // Bauzeit je nach Level erhöhen
        return 60 * pow(1.2, $building->level - 1);
    }

    private function updateBuildingCosts(Building $building, $costs)
    {
        BuildingResourceCost::where('building_id', $building->id)->delete();

        $resources = Resource::pluck('id', 'name')->toArray();

        foreach ($costs as $cost) {
            if (isset($resources[$cost['resource_name']])) {
                BuildingResourceCost::create([
                    'building_id' => $building->id,
                    'resource_id' => $resources[$cost['resource_name']],
                    'amount' => $cost['amount'],
                ]);
            }
        }
    }
}
