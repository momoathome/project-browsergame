<?php

namespace Orion\Modules\User\Repositories;

use Illuminate\Support\Collection;
use Orion\Modules\User\Models\UserResource;

readonly class UserResourceRepository
{
    // Add repository logic here
    public function getAllUserResourcesByUserId(int $userId): Collection
    {
        return UserResource::with('resource')
            ->where('user_id', $userId)
            ->orderBy('resource_id', 'asc')
            ->get();
    }

    public function getSpecificUserResource(int $userId, int $resourceId): UserResource|null 
    {
        return UserResource::with('resource')
            ->where('user_id', $userId)
            ->where('resource_id', $resourceId)
            ->first();
    }

    public function getResourceIdByName(string $name): string
    {
        return UserResource::whereHas('resource', function ($query) use ($name) {
            $query->where('name', $name);
        })->first();
    }

    public function updateResourceAmount(int $userId, int $resourceId, int $amount)
    {
        $userResource = $this->getSpecificUserResource($userId, $resourceId);
        $userResource->amount = $amount;
        $userResource->save();
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

    public function createUserResource(int $userId, int $resourceId, int $amount)
    {
        UserResource::create([
            'user_id' => $userId,
            'resource_id' => $resourceId,
            'amount' => $amount,
        ]);
    }
}
