<?php

namespace Orion\Modules\User\Services;

use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\User\Models\UserResource;

class SetupInitialUserResources
{
    /**
     * Setup initial resources for a new user.
     *
     * @param int $userId
     */
    public function create(int $userId): void
    {
        $resourcesConfig = config('game.user_resources.resources');
        $resources = Resource::pluck('id', 'name')->toArray();

        foreach ($resourcesConfig as $resourceConfig) {
            UserResource::create([
                'user_id' => $userId,
                'resource_id' => $resources[$resourceConfig['name']],
                'amount' => $resourceConfig['amount'],
            ]);
        }
    }

    public function reset(int $userId): void
    {
        UserResource::where('user_id', $userId)->delete();
    }
}
