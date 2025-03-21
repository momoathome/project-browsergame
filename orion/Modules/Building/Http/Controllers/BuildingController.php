<?php

namespace Orion\Modules\Building\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\User\Models\UserResource;
use Orion\Modules\User\Models\UserAttribute;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Building\Services\BuildingCostCalculator;
use Orion\Modules\Building\Services\BuildingUpgradeService;

class BuildingController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly BuildingUpgradeService $buildingUpgradeService,
        private readonly BuildingCostCalculator $costCalculator,
        private readonly AuthManager $authManager
    ) {
    }

    public function index()
    {
        $user = $this->authManager->user();
        $buildings = Building::with('details', 'resources')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        // Queue-Informationen holen
        $buildingQueues = $this->queueService->getInProgressQueuesByType($user->id, ActionQueue::ACTION_TYPE_BUILDING);

        // Queue-Infos den Gebäuden hinzufügen
        $buildings = $buildings->map(function ($building) use ($buildingQueues) {
            $isUpgrading = isset($buildingQueues[$building->id]);
            $building->is_upgrading = $isUpgrading;
            $building->next_level_costs = $this->costCalculator->calculateUpgradeCost($building);

            if ($isUpgrading) {
                $building->end_time = $buildingQueues[$building->id]->end_time;
            }

            return $building;
        });

        return Inertia::render('Buildings', [
            'buildings' => $buildings,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Building $building)
    {
        $user = $this->authManager->user();
        $nextLevelCosts = $this->costCalculator->calculateUpgradeCost($building);
        $requiredResourcesMap = collect($nextLevelCosts)->pluck('amount', 'resource_name')->toArray();

        $resourceIds = [];
        $resourceAmounts = [];

        // Konvertiere die ressourcennamen in IDs für die Abfrage
        $resources = Resource::whereIn('name', array_keys($requiredResourcesMap))
            ->get()
            ->each(function ($resource) use ($requiredResourcesMap, &$resourceIds, &$resourceAmounts) {
                $resourceIds[] = $resource->id;
                $resourceAmounts[$resource->id] = $requiredResourcesMap[$resource->name];
            });

        // Prüfen, ob genügend Ressourcen vorhanden sind
        $userResources = UserResource::where('user_id', $user->id)
            ->whereIn('resource_id', $resourceIds)
            ->get();

        foreach ($userResources as $userResource) {
            $requiredAmount = $resourceAmounts[$userResource->resource_id] ?? 0;
            if ($userResource->amount < $requiredAmount) {
                return redirect()->route('buildings')->dangerBanner('Not enough resources');
            }
        }

        // Ressourcen abziehen in einer Transaktion
        DB::transaction(function () use ($user, $building, $resourceIds, $resourceAmounts) {
            foreach ($resourceIds as $resourceId) {
                UserResource::where('user_id', $user->id)
                    ->where('resource_id', $resourceId)
                    ->decrement('amount', $resourceAmounts[$resourceId]);
            }

            // Upgrade zur Queue hinzufügen
            $this->queueService->addToQueue(
                $user->id,
                ActionQueue::ACTION_TYPE_BUILDING,
                $building->id,
                $building->build_time, // Dauer in Sekunden
                [
                    'building_name' => $building->details->name,
                    'current_level' => $building->level,
                    'next_level' => $building->level + 1,
                ]
            );
        });

        return redirect()->route('buildings')->banner('Building upgrade started');
    }

    /**
     * Abschluss eines Gebäude-Upgrades
     */
    public function completeUpgrade($buildingId, $userId)
    {
        $building = Building::where('id', $buildingId)
            ->where('user_id', $userId)
            ->first();

        if (!$building) {
            return false;
        }

        return DB::transaction(function () use ($building, $userId) {
            // Upgrade durchführen
            $this->buildingUpgradeService->upgradeBuilding($building);

            // User-Attribute basierend auf dem Gebäudetyp aktualisieren
            $buildingEffects = [
                'Shipyard' => ['production_speed' => $building->effect_value - 1, 'replace' => true],
                'Hangar' => ['crew_limit' => $building->effect_value],
                'Warehouse' => ['storage' => $building->effect_value, 'multiply' => true],
                'Laboratory' => ['research_points' => $building->effect_value],
                'Scanner' => ['scan_range' => $building->effect_value],
                'Shield' => ['base_defense' => $building->effect_value - 1, 'replace' => true],
            ];

            if (isset($buildingEffects[$building->details->name])) {
                foreach ($buildingEffects[$building->details->name] as $attributeName => $value) {
                    if (!is_array($value)) { // Ignoriere die Flags 'multiply' und 'replace'
                        $this->updateUserAttribute(
                            $userId,
                            $attributeName,
                            $value,
                            $buildingEffects[$building->details->name]['multiply'] ?? false,
                            $buildingEffects[$building->details->name]['replace'] ?? false
                        );
                    }
                }
            }

            return true;
        });
    }

    private function updateUserAttribute($userId, $attributeName, $value, $multiply = false, $replace = false)
    {
        $userAttribute = UserAttribute::where('user_id', $userId)
            ->where('attribute_name', $attributeName)
            ->first();

        if ($userAttribute) {
            if ($multiply) {
                $userAttribute->attribute_value = round($userAttribute->attribute_value * $value);
            } else if ($replace) {
                $userAttribute->attribute_value = $value;
            } else {
                $userAttribute->attribute_value += $value;
            }
            $userAttribute->save();
        }
    }
}
