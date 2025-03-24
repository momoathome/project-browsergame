<?php

namespace Orion\Modules\Market\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Market\Exceptions\InsufficientCreditsException;
use Orion\Modules\Market\Exceptions\InsufficientResourceException;
use Orion\Modules\Market\Exceptions\InsufficientStorageException;
use Orion\Modules\Market\Repositories\MarketRepository;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\User\Services\UserResourceService;

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

    public function buyResource($resourceId, $quantity)
    {
        $user = $this->authManager->user();

        try {
            DB::transaction(function () use ($resourceId, $quantity, $user) {
                $marketItem = $this->marketRepository->getMarketItem($resourceId);
                $totalCost = $marketItem->cost * $quantity;

                // Validate user can afford the purchase
                $userCreditsAttribute = $this->userAttributeService->getSpecificUserAttribute($user->id, 'credits');
                if (!$userCreditsAttribute || $userCreditsAttribute->attribute_value < $totalCost) {
                    throw new InsufficientCreditsException();
                }

                // Validate market has enough stock
                if ($marketItem->stock < $quantity) {
                    throw new InsufficientResourceException();
                }

                // Validate user has enough storage
                $userStorageAttribute = $this->userAttributeService->getSpecificUserAttribute($user->id, 'storage');
                if ($userStorageAttribute->attribute_value < $quantity) {
                    throw new InsufficientStorageException();
                }

                // Update market stock
                $this->marketRepository->decreaseStock($resourceId, $quantity);

                // Update user credits
                $this->userAttributeService->subtractAttributeAmount($user->id, 'credits', $totalCost);

                // Add resources to user inventory
                $userResource = $this->userResourceService->getSpecificUserResource($user->id, $marketItem->resource_id);

                if ($userResource) {
                    $this->userResourceService->addResourceAmount($user->id, $marketItem->resource_id, $quantity);
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

    public function sellResource($resourceId, $quantity)
    {
        $user = $this->authManager->user();

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
                $this->userResourceService->subtractResourceAmount($user->id, $marketItem->resource_id, $quantity);

                // Update market stock
                $this->marketRepository->increaseStock($resourceId, $quantity);

                // Add credits to user
                $this->userAttributeService->addAttributeAmount($user->id, 'credits', $totalEarnings);
            });

            return redirect()->route('market')->banner('Resource sold successfully');
        } catch (InsufficientResourceException $e) {
            return redirect()->route('market')->dangerBanner('Not enough resources');
        } catch (\Exception $e) {
            return redirect()->route('market')->dangerBanner('Transaction failed: ' . $e->getMessage());
        }
    }
}
