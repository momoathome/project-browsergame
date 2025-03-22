<?php

namespace Orion\Modules\Spacecraft\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Orion\Modules\User\Models\UserResource;
use Orion\Modules\User\Models\UserAttribute;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class SpacecraftController extends Controller
{
    public function __construct(
        private readonly SpacecraftService $spacecraftService,
        private readonly QueueService $queueService,
        private readonly AuthManager $authManager
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = $this->authManager->user();
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetailsAndResources($user->id);

        // Queue-Informationen holen
        $spacecraftQueues = $this->queueService->getInProgressQueuesFromUserByType($user->id, ActionQueue::ACTION_TYPE_PRODUCE);

        // Queue-Infos den Raumschiffen hinzufügen
        $spacecrafts = $spacecrafts->map(function ($spacecraft) use ($spacecraftQueues) {
            $isProducing = isset($spacecraftQueues[$spacecraft->id]);
            $spacecraft->is_producing = $isProducing;

            if ($isProducing) {
                $spacecraft->end_time = $spacecraftQueues[$spacecraft->id]->end_time;
                $spacecraft->currently_producing = $spacecraftQueues[$spacecraft->id]->details['quantity'] ?? 0;
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

        return DB::transaction(function () use ($spacecraft, $details) {
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            $quantity = $details['quantity'];

            $spacecraft->count += $quantity;
            $spacecraft->save();

            return true;
        });
    }

    public function unlock(Spacecraft $spacecraft)
    {
        $user = $this->authManager->user();
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
