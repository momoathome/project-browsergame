<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserResource;
use App\Models\UserAttribute;
use App\Models\BuildingResourceCost;
use App\Services\QueueService;
use App\Models\ActionQueue;

class BuildingController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Display a listing of the resource.
     */
    // In der index-Methode:

    public function index()
    {
        $user = auth()->user();

        $this->queueService->processQueueForUser($user->id);

        $buildings = Building::with('details', 'resources')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        // Queue-Informationen holen
        $buildingQueues = $this->queueService->getPendingQueuesByType($user->id, ActionQueue::ACTION_TYPE_BUILDING);

        // Queue-Infos den Gebäuden hinzufügen
        $buildings = $buildings->map(function ($building) use ($buildingQueues) {
            $isUpgrading = isset($buildingQueues[$building->id]);
            $building->is_upgrading = $isUpgrading;

            if ($isUpgrading) {
                $building->upgrade_end_time = $buildingQueues[$building->id]->end_time;
            }

            return $building;
        });

        return Inertia::render('Buildings', [
            'buildings' => $buildings,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(building $building)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(building $building)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Building $building)
    {
        $user = auth()->user();
        $requiredResources = BuildingResourceCost::where('building_id', $building->id)->get();

        // Prüfen, ob genügend Ressourcen vorhanden sind
        foreach ($requiredResources as $requiredResource) {
            $userResource = UserResource::where('user_id', $user->id)
                ->where('resource_id', $requiredResource->resource_id)
                ->first();

            if (!$userResource || $userResource->amount < $requiredResource->amount) {
                return back()->with('error', 'Not enough resources');
            }
        }

        // Ressourcen abziehen in einer Transaktion
        DB::transaction(function () use ($user, $building, $requiredResources) {
            foreach ($requiredResources as $requiredResource) {
                UserResource::where('user_id', $user->id)
                    ->where('resource_id', $requiredResource->resource_id)
                    ->decrement('amount', $requiredResource->amount);
            }

            // Upgrade zur Queue hinzufügen
            $this->queueService->addToQueue(
                $user->id,
                ActionQueue::ACTION_TYPE_BUILDING,
                $building->id,
                $building->build_time, // Dauer in Sekunden
                [
                    'building_id' => $building->id,
                    'current_level' => $building->level
                ]
            );
        });

        return back()->with('success', 'Building upgrade started');
    }

    public function completeUpgrade($buildingId, $userId)
    {
        $building = Building::where('id', $buildingId)
            ->where('user_id', $userId)
            ->first();

        if (!$building) {
            return false;
        }

        return DB::transaction(function () use ($building, $userId) {
            $user = \App\Models\User::find($userId);

            $building->level += 1;
            $building->build_time = $this->calculateNewBuildTime($building);
            $building->save();

            /* name and effect of each building if multiply = the effect is a multiplier instead of additive */
            $buildingEffects = [
                'Hangar' => ['unit_limit' => 10],
                'Warehouse' => ['storage' => 1.3, 'multiply' => true],
                'Laboratory' => ['research_points' => 2],
                'Scanner' => ['scan_range' => 5000],
            ];

            if (isset($buildingEffects[$building->details->name])) {
                foreach ($buildingEffects[$building->details->name] as $attributeName => $value) {
                    if ($attributeName !== 'multiply') {
                        $this->updateUserAttribute($user->id, $attributeName, $value, $buildingEffects[$building->details->name]['multiply'] ?? false);
                    }
                }
            }

            $this->updateResourceCosts($building);

            return true;
        });
    }

    private function updateUserAttribute($userId, $attributeName, $value, $multiply = false)
    {
        $userAttribute = UserAttribute::where('user_id', $userId)
            ->where('attribute_name', $attributeName)
            ->first();

        if ($userAttribute) {
            if ($multiply) {
                $userAttribute->attribute_value = round($userAttribute->attribute_value * $value);
            } else {
                $userAttribute->attribute_value += $value;
            }
            $userAttribute->save();
        }
    }

    private function calculateNewBuildTime(Building $building)
    {
        return round($building->build_time * 1.2);
    }

    private function updateResourceCosts(Building $building)
    {
        $costIncreaseFactor = 1.35; // 35% Erhöhung
        $building->load('resources');

        foreach ($building->resources as $resource) {
            $currentAmount = $resource->pivot->amount;
            $newAmount = round($currentAmount * $costIncreaseFactor);
            $building->resources()->updateExistingPivot($resource->id, ['amount' => $newAmount]);
        }
    }
}
