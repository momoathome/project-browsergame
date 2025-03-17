<?php

namespace App\Http\Controllers;

use App\Models\Spacecraft;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserResource;
use App\Models\UserAttribute;
use App\Services\QueueService;
use App\Models\ActionQueue;

class SpacecraftController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Hole den aktuell angemeldeten Benutzer
        $user = auth()->user();

        $this->queueService->processQueueForUser($user->id);

        $spacecrafts = Spacecraft::with('details', 'resources')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        // Queue-Informationen holen
        $spacecraftQueues = $this->queueService->getInProgressQueuesByType($user->id, ActionQueue::ACTION_TYPE_PRODUCE);

        // Queue-Infos den Raumschiffen hinzufügen
        $spacecrafts = $spacecrafts->map(function ($spacecraft) use ($spacecraftQueues) {
            $isProducing = isset($spacecraftQueues[$spacecraft->id]);
            $spacecraft->is_producing = $isProducing;

            if ($isProducing) {
                $spacecraft->production_end_time = $spacecraftQueues[$spacecraft->id]->end_time;
                $spacecraft->currently_producing = $spacecraftQueues[$spacecraft->id]->details['quantity'];
            }

            return $spacecraft;
        });

        return Inertia::render('Shipyard', [
            'spacecrafts' => $spacecrafts,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Spacecraft $spacecraft)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Spacecraft $spacecraft)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Spacecraft $spacecraft)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $quantity = $validated['amount'];
        $user = auth()->user();

        $totalCosts = $spacecraft->resources->mapWithKeys(function ($resource) use ($quantity) {
            return [$resource->id => $resource->pivot->amount * $quantity];
        });

        $crewLimit = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'crew_limit')
            ->first();

        if ($crewLimit && $crewLimit->attribute_value < $spacecraft->crew_limit * $quantity) {
            return redirect()->route('shipyard')->dangerBanner('maximum Crew Limit reached');
        }

        foreach ($totalCosts as $resourceId => $requiredResource) {
            $userResource = UserResource::where('user_id', $user->id)
                ->where('resource_id', $resourceId)
                ->first();

            if (!$userResource || $userResource->amount < $requiredResource) {
                return redirect()->route('shipyard')->dangerBanner('Not enough resources');
            }
        }

        // Ressourcen abziehen in einer Transaktion
        DB::transaction(function () use ($user, $quantity, $totalCosts, $spacecraft) {
            foreach ($totalCosts as $resourceId => $requiredResource) {
                UserResource::where('user_id', $user->id)
                    ->where('resource_id', $resourceId)
                    ->decrement('amount', $requiredResource);
            }

            UserAttribute::where('user_id', $user->id)
                ->where('attribute_name', 'total_units')
                ->increment('attribute_value', $spacecraft->crew_limit * $quantity);

            // Produktion zur Queue hinzufügen
            $this->queueService->addToQueue(
                $user->id,
                ActionQueue::ACTION_TYPE_PRODUCE,
                $spacecraft->id,
                $spacecraft->build_time * $quantity,
                [
                    'spacecraft_name' => $spacecraft->details->name,
                    'quantity' => $quantity,
                ]
            );
        });

        return redirect()->route('shipyard')->banner('Production started');
    }

    public function completeProduction($spacecraft, $userId, $details)
    {
        $spacecraft = Spacecraft::where('id', $spacecraft)
            ->where('user_id', $userId)
            ->first();

        if (!$spacecraft) {
            return false;
        }

        return DB::transaction(function () use ($spacecraft, $userId, $details) {
            $quantity = $details['quantity'];

            $spacecraft->count += $quantity;
            $spacecraft->save();

            return true;
        });
    }

    public function unlock(Spacecraft $spacecraft)
    {
        $user = auth()->user();
        $researchPointsAttribute = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'research_points')
            ->first();

        if (!$researchPointsAttribute || $researchPointsAttribute->attribute_value < $spacecraft->research_cost) {
            return redirect()->route('shipyard')->dangerBanner('Not enough research points');
        }

        UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'research_points')
            ->decrement('attribute_value', $spacecraft->research_cost);

        $spacecraft->unlocked = true;
        $spacecraft->save();

        return redirect()->route('shipyard')->banner('Spacecraft unlocked successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spacecraft $spacecraft)
    {
        //
    }

}
