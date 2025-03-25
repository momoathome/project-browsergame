<?php

namespace Orion\Modules\User\Services;

use Illuminate\Support\Collection;
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

    public function updateResourceAmount(int $userId, int $resourceId, int $amount)
    {
        return $this->userResourceRepository->updateResourceAmount($userId, $resourceId, $amount);
    }

    public function addResourceAmount(int $userId, int $resourceId, int $amount)
    {
        return $this->userResourceRepository->addResourceAmount($userId, $resourceId, $amount);
    }

    public function subtractResourceAmount(int $userId, int $resourceId, int $amount)
    {
        return $this->userResourceRepository->subtractResourceAmount($userId, $resourceId, $amount);
    }

    public function createUserResource(int $userId, int $resourceId, int $amount)
    {
        return $this->userResourceRepository->createUserResource($userId, $resourceId, $amount);
    }

    /**
     * Prüft, ob der Benutzer genügend Ressourcen hat
     * 
     * @param int $userId ID des Benutzers
     * @param Collection $requiredResources
     * @throws \Exception wenn nicht genügend Ressourcen vorhanden sind
     */
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

    /**
     * Zieht die benötigten Ressourcen vom Benutzer ab
     * 
     * @param int $userId ID des Benutzers
     * @param Collection $requiredResources Collection von Ressourcen mit ['id', 'name', 'amount']
     */
    public function decrementUserResources(int $userId, Collection $requiredResources): void
    {
        $requiredResources->each(function ($resource, $resourceId) use ($userId) {
            $this->subtractResourceAmount($userId, $resourceId, $resource['amount']);
        });
    }
}
