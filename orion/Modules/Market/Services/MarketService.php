<?php

namespace Orion\Modules\Market\Services;

use Orion\Modules\User\Models\UserAttribute;
use Orion\Modules\User\Models\UserResource;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Market\Models\Market;
use Orion\Modules\Market\Repositories\MarketRepository;

readonly class MarketService
{

    public function __construct(
        private readonly MarketRepository $marketRepository,
        private readonly AuthManager $authManager
        )
    {
    }

    public function buyResource($resourceId, $quantity)
    {
        $user = $this->authManager->user();

        $marketItem = Market::findOrFail($resourceId);

        $userCreditsAttribute = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'credits')
            ->first();

        $userStorageAttribute = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'storage')
            ->first();

        $totalCost = $marketItem->cost * $quantity;

        if (!$userCreditsAttribute || $userCreditsAttribute->attribute_value < $totalCost) {
            return redirect()->route('market')->dangerBanner('Not enough credits');
        }

        if ($marketItem->stock < $quantity) {
            return redirect()->route('market')->dangerBanner('Not enough stock');
        }

        if ($userStorageAttribute->attribute_value < $quantity) {
            return redirect()->route('market')->dangerBanner('Not enough storage');
        }

        DB::transaction(function () use ($marketItem, $user, $quantity, $totalCost, $userCreditsAttribute) {
            $marketItem->stock -= $quantity;
            $marketItem->save();

            $userCreditsAttribute->attribute_value -= $totalCost;
            $userCreditsAttribute->save();

            $userResource = UserResource::where('user_id', $user->id)
                ->where('resource_id', $marketItem->resource_id)
                ->first();

            if ($userResource) {
                $userResource->amount += $quantity;
                $userResource->save();
            } else {
                UserResource::create([
                    'user_id' => $user->id,
                    'resource_id' => $marketItem->resource_id,
                    'amount' => $quantity,
                ]);
            }
        });

        return redirect()->route('market')->banner('Resource purchased successfully');
    }

    public function sellResource($resourceId, $quantity)
    {
        $user = $this->authManager->user();
        $marketItem = Market::findOrFail($resourceId);

        $userResource = UserResource::where('user_id', $user->id)
            ->where('resource_id', $marketItem->resource_id)
            ->first();

        if (!$userResource || $userResource->amount < $quantity) {
            return redirect()->route('market')->dangerBanner('Not enough resources');
        }

        $userCreditsAttribute = UserAttribute::where('user_id', $user->id)
            ->where('attribute_name', 'credits')
            ->first();

        $totalCost = $marketItem->cost * $quantity;

        DB::transaction(function () use ($marketItem, $quantity, $userResource, $totalCost, $userCreditsAttribute) {
            $userResource->amount -= $quantity;
            $userResource->save();

            $marketItem->stock += $quantity;
            $marketItem->save();

            $userCreditsAttribute->attribute_value += $totalCost;
            $userCreditsAttribute->save();
        });

        return redirect()->route('market')->banner('Resource sold successfully');
    }
}
