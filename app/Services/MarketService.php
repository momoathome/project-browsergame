<?php

namespace App\Services;

use App\Models\Market;
use App\Models\Resource;
use App\Models\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketService
{
  public function buyResource($resourceId, $quantity)
  {
    $user = Auth::user();

    $marketItem = Market::findOrFail($resourceId);
    $creditResource = Resource::where('name', 'Credits')->firstOrFail();
    $userCreditResource = UserResource::where('user_id', $user->id)
      ->where('resource_id', $creditResource->id)
      ->first();

    $totalCost = $marketItem->cost * $quantity;

    if (!$userCreditResource || $userCreditResource->count < $totalCost) {
      throw new \Exception('Not enough Credits');
    }

    if ($marketItem->stock < $quantity) {
      throw new \Exception('Not enough stock');
    }

    DB::transaction(function () use ($marketItem, $user, $quantity, $totalCost, $userCreditResource) {
      $marketItem->stock -= $quantity;
      $marketItem->save();

      $userCreditResource->count -= $totalCost;
      $userCreditResource->save();

      $userResource = UserResource::where('user_id', $user->id)
        ->where('resource_id', $marketItem->resource_id)
        ->first();

      if ($userResource) {
        $userResource->count += $quantity;
        $userResource->save();
      } else {
        UserResource::create([
          'user_id' => $user->id,
          'resource_id' => $marketItem->resource_id,
          'count' => $quantity,
        ]);
      }
    });

    return redirect()->route('market')->banner('Resource purchased successfully');
  }

  public function sellResource($resourceId, $quantity)
  {
    $user = Auth::user();

    $marketItem = Market::findOrFail($resourceId);
    $creditResource = Resource::where('name', 'Credits')->firstOrFail();
    $userCreditResource = UserResource::where('user_id', $user->id)
      ->where('resource_id', $creditResource->id)
      ->first();

    $totalCost = $marketItem->cost * $quantity;

    $userResource = UserResource::where('user_id', $user->id)
      ->where('resource_id', $marketItem->resource_id)
      ->first();

    if (!$userResource || $userResource->count < $quantity) {
      throw new \Exception('Not enough resources');
    }

    DB::transaction(function () use ($marketItem, $quantity, $userResource, $totalCost, $userCreditResource) {
      $userResource->count -= $quantity;
      $userResource->save();

      $marketItem->stock += $quantity;
      $marketItem->save();

      $userCreditResource->count += $totalCost;
      $userCreditResource->save();
    });

    return redirect()->route('market')->banner('Resource sold successfully');
  }
}
