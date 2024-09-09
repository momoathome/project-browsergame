<?php

namespace App\Services;

use App\Models\Market;
use App\Models\Resource;
use App\Models\UserResource;
use App\Models\UserAttribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketService
{
  public function buyResource($resourceId, $quantity)
  {
    $user = Auth::user();
    $marketItem = Market::findOrFail($resourceId);

    $userCreditsAttribute = UserAttribute::where('user_id', $user->id)
      ->where('attribute_name', 'credits')
      ->first();

    $totalCost = $marketItem->cost * $quantity;

    if (!$userCreditsAttribute || $userCreditsAttribute->attribute_value < $totalCost) {
      throw new \Exception('Not enough Credits');
    }

    if ($marketItem->stock < $quantity) {
      throw new \Exception('Not enough stock');
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
    $user = Auth::user();
    $marketItem = Market::findOrFail($resourceId);

    $userCreditsAttribute = UserAttribute::where('user_id', $user->id)
      ->where('attribute_name', 'credits')
      ->first();

    $totalCost = $marketItem->cost * $quantity;

    $userResource = UserResource::where('user_id', $user->id)
      ->where('resource_id', $marketItem->resource_id)
      ->first();

    if (!$userResource || $userResource->amount < $quantity) {
      throw new \Exception('Not enough resources');
    }

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
