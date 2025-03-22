<?php

namespace Orion\Modules\User\Services;

use Orion\Modules\User\Repositories\UserResourceRepository;

class UserResourceService
{
    public function __construct(
        private readonly UserResourceRepository $userResourceRepository
    ) {
    }
    public function getAllUserResourcesByUserId(int $userId)
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
        $userResource = $this->getSpecificUserResource($userId, $resourceId);
        $userResource->amount += $amount;
        $userResource->save();
    }

    public function subtractResourceAmount(int $userId, int $resourceId, int $amount)
    {
        $userResource = $this->getSpecificUserResource($userId, $resourceId);
        $userResource->amount -= $amount;
        $userResource->save();
    }
}
