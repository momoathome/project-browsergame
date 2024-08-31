<?php

namespace App\Services;

use App\Models\UserResource;
use App\Models\Resource;

class SetupInitialUserResources
{
    /**
     * Setup initial resources for a new user.
     *
     * @param int $userId
     */
    public function create(int $userId): void
    {
        $resourcesConfig = config('user_resources.resources');
        $resources = Resource::pluck('id', 'name')->toArray();

        foreach ($resourcesConfig as $resourceConfig) {
            UserResource::create([
                'user_id' => $userId,
                'resource_id' => $resources[$resourceConfig['name']],
                'amount' => $resourceConfig['amount'],
            ]);
        }
    }
}
