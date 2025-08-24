<?php

namespace Orion\Modules\Market\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Market\Models\Market;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Market\Repositories\MarketRepository;
use Orion\Modules\Market\Exceptions\InsufficientCreditsException;
use Orion\Modules\Market\Exceptions\InsufficientStorageException;
use Orion\Modules\Market\Exceptions\InsufficientResourceException;

readonly class MarketService
{
    public function __construct(
        private readonly MarketRepository $marketRepository,
        private readonly AuthManager $authManager,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService
    ) {
    }

    public function getMarketData()
    {
        return $this->marketRepository->getMarketData();
    }

    public function updateResourceAmount($resourceId, $stock, $cost)
    {
        $this->marketRepository->updateResourceAmount($resourceId, $stock, $cost);
    }

    public function buyResource($user, Market $marketRes, $quantity)
    {
        $totalCost = $marketRes->cost * $quantity;
        try {
            DB::transaction(function () use ($marketRes, $quantity, $user, $totalCost) {

                // Validate user can afford the purchase
                $userCreditsAttribute = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::CREDITS);
                if (!$userCreditsAttribute || $userCreditsAttribute->attribute_value < $totalCost) {
                    throw new InsufficientCreditsException();
                }

                // Validate market has enough stock
                if ($marketRes->stock < $quantity) {
                    throw new InsufficientResourceException();
                }

                // Validate user has enough storage
                $userResource = $this->userResourceService->getSpecificUserResource($user->id, $marketRes->resource_id);
                $userStorageAttribute = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::STORAGE);
                if ($userResource && $userStorageAttribute && $userResource->amount + $quantity > $userStorageAttribute->attribute_value) {
                    throw new InsufficientStorageException();
                }

                // Update market stock
                $this->marketRepository->decreaseStock($marketRes->resource_id, $quantity);

                // Update user credits
                $this->userAttributeService->subtractAttributeAmount($user->id, UserAttributeType::CREDITS, $totalCost);

                if ($userResource) {
                    $this->userResourceService->addResourceAmount($user, $marketRes->resource_id, $quantity);
                } else {
                    $this->userResourceService->createUserResource($user->id, $marketRes->resource_id, $quantity);
                }
            });


            broadcast(new UpdateUserResources($user));
            $totalCostFormatted = number_format($totalCost, 0, ',', '.');
            return redirect()->route('market')->banner("Resource {$marketRes->resource->name} x{$quantity} purchased successfully for {$totalCostFormatted} credits");
        } catch (InsufficientCreditsException $e) {
            return redirect()->route('market')->dangerBanner('Not enough credits');
        } catch (InsufficientResourceException $e) {
            return redirect()->route('market')->dangerBanner('Not enough stock');
        } catch (InsufficientStorageException $e) {
            return redirect()->route('market')->dangerBanner('Not enough storage');
        } catch (\Exception $e) {
            return redirect()->route('market')->dangerBanner('Transaction failed: ' . $e->getMessage());
        }
    }

    public function sellResource($user, $marketRes, $quantity)
    {
        $totalEarnings = $marketRes->cost * $quantity;

        try {
            DB::transaction(function () use ($marketRes, $quantity, $user, $totalEarnings) {
                // Check if user has enough resources
                $userResource = $this->userResourceService->getSpecificUserResource($user->id, $marketRes->resource_id);
                if (!$userResource || $userResource->amount < $quantity) {
                    throw new InsufficientResourceException();
                }

                // Subtract resources from user
                $this->userResourceService->subtractResourceAmount($user, $marketRes->resource_id, $quantity);

                // Update market stock
                $this->marketRepository->increaseStock($marketRes->resource_id, $quantity);

                // Add credits to user
                $this->userAttributeService->addAttributeAmount($user->id, UserAttributeType::CREDITS, $totalEarnings);
            });

            broadcast(new UpdateUserResources($user));
            $totalEarningsFormatted = number_format($totalEarnings, 0, ',', '.');
            return redirect()->route('market')->banner("Resource {$marketRes->resource->name} x{$quantity} sold successfully for {$totalEarningsFormatted} credits");
        } catch (InsufficientResourceException $e) {
            return redirect()->route('market')->dangerBanner('Not enough resources');
        } catch (\Exception $e) {
            return redirect()->route('market')->dangerBanner('Transaction failed: ' . $e->getMessage());
        }
    }
}
