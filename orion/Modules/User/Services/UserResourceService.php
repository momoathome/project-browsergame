<?php

namespace Orion\Modules\User\Services;

use Illuminate\Support\Collection;
use Orion\Modules\User\Repositories\UserResourceRepository;

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
}
