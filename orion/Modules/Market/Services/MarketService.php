<?php

namespace Orion\Modules\Market\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
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

    public function buyResource($user, $resourceId, $quantity)
    {
        try {
            DB::transaction(function () use ($resourceId, $quantity, $user) {
                $marketItem = $this->marketRepository->getMarketItem($resourceId);
                $totalCost = $marketItem->cost * $quantity;

                // Validate user can afford the purchase
                $userCreditsAttribute = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::CREDITS);
                if (!$userCreditsAttribute || $userCreditsAttribute->attribute_value < $totalCost) {
                    throw new InsufficientCreditsException();
                }

                // Validate market has enough stock
                if ($marketItem->stock < $quantity) {
                    throw new InsufficientResourceException();
                }

                // Validate user has enough storage
                $userResource = $this->userResourceService->getSpecificUserResource($user->id, $marketItem->resource_id);
                $userStorageAttribute = $this->userAttributeService->getSpecificUserAttribute($user->id, UserAttributeType::STORAGE);
                if ($userResource && $userStorageAttribute && $userResource->amount + $quantity > $userStorageAttribute->attribute_value) {
                    throw new InsufficientStorageException();
                }

                // Update market stock
                $this->marketRepository->decreaseStock($resourceId, $quantity);

                // Update user credits
                $this->userAttributeService->subtractAttributeAmount($user->id, UserAttributeType::CREDITS, $totalCost);

                if ($userResource) {
                    $this->userResourceService->addResourceAmount($user, $marketItem->resource_id, $quantity);
                } else {
                    $this->userResourceService->createUserResource($user->id, $marketItem->resource_id, $quantity);
                }
            });

            return redirect()->route('market')->banner('Resource purchased successfully');
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

    public function sellResource($user, $resourceId, $quantity)
    {
        try {
            DB::transaction(function () use ($resourceId, $quantity, $user) {
                $marketItem = $this->marketRepository->getMarketItem($resourceId);
                $totalEarnings = $marketItem->cost * $quantity;

                // Check if user has enough resources
                $userResource = $this->userResourceService->getSpecificUserResource($user->id, $marketItem->resource_id);
                if (!$userResource || $userResource->amount < $quantity) {
                    throw new InsufficientResourceException();
                }

                // Subtract resources from user
                $this->userResourceService->subtractResourceAmount($user, $marketItem->resource_id, $quantity);

                // Update market stock
                $this->marketRepository->increaseStock($resourceId, $quantity);

                // Add credits to user
                $this->userAttributeService->addAttributeAmount($user->id, UserAttributeType::CREDITS, $totalEarnings);
            });

            return redirect()->route('market')->banner('Resource sold successfully');
        } catch (InsufficientResourceException $e) {
            return redirect()->route('market')->dangerBanner('Not enough resources');
        } catch (\Exception $e) {
            return redirect()->route('market')->dangerBanner('Transaction failed: ' . $e->getMessage());
        }
    }
}
