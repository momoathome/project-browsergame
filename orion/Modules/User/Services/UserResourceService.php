<?php

namespace Orion\Modules\User\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use App\Events\UpdateUserResources;
use Orion\Modules\User\Repositories\UserResourceRepository;
use Orion\Modules\Resource\Exceptions\InsufficientResourceException;

class UserResourceService
{
    public function __construct(
        private readonly UserResourceRepository $userResourceRepository
    ) {
    }
    public function getAllUserResourcesByUserId(int $userId): Collection
    {
        return $this->userResourceRepository->getAllUserResourcesByUserId($userId);
    }

    public function getSpecificUserResource(int $userId, int $resourceId)
    {
        return $this->userResourceRepository->getSpecificUserResource($userId, $resourceId);
    }

    public function updateResourceAmount(int $userId, int $resourceId, int $amount): void
    {
        $this->userResourceRepository->updateResourceAmount($userId, $resourceId, $amount);
    }

    public function addResourceAmount(User $user, int $resourceId, int $amount): void
    {
        $this->userResourceRepository->addResourceAmount($user->id, $resourceId, $amount);
        broadcast(new UpdateUserResources($user));
    }

    public function subtractResourceAmount(User $user, int $resourceId, int $amount): void
    {
        $this->userResourceRepository->subtractResourceAmount($user->id, $resourceId, $amount);
    }

    public function createUserResource(int $userId, int $resourceId, int $amount)
    {
        return $this->userResourceRepository->createUserResource($userId, $resourceId, $amount);
    }

    public function validateUserHasEnoughResources(int $userId, Collection $requiredResources): void
    {
        $userResources = $this->getAllUserResourcesByUserId($userId)
            ->keyBy('resource_id');

        $requiredResources->each(function ($resourceCost, $resourceId) use ($userResources) {
            $userResource = $userResources->get($resourceId);
            $requiredAmount = $resourceCost['amount'];

            if (!$userResource || $userResource->amount < $requiredAmount) {
                $resourceName = $resourceCost['name'] ?? 'Ressource #' . $resourceId;
                throw new InsufficientResourceException($resourceName);
            }
        });
    }

    public function decrementUserResources(User $user, Collection $requiredResources): void
    {
        $requiredResources->each(function ($resource, $resourceId) use ($user): void {
            $this->subtractResourceAmount($user, $resourceId, $resource['amount']);
        });
    }
}
