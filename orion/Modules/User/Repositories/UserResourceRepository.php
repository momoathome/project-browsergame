<?php

namespace Orion\Modules\User\Repositories;

use Orion\Modules\User\Models\UserResource;

readonly class UserResourceRepository
{
    // Add repository logic here
    public function getAllUserResourcesByUserId(int $userId)
    {
        return UserResource::with('resource')
            ->where('user_id', $userId)
            ->orderBy('resource_id', 'asc')
            ->get();
    }

    public function getSpecificUserResource(int $userId, int $resourceId) 
    {
        return UserResource::with('resource')
        ->where('user_id', $userId)
        ->where('resource_id', $resourceId)
        ->first();
    }

    public function updateResourceAmount(int $userId, int $resourceId, int $amount)
    {
        $userResource = $this->getSpecificUserResource($userId, $resourceId);
        $userResource->amount = $amount;
        $userResource->save();
    }
}
