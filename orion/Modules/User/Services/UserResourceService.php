<?php

namespace Orion\Modules\User\Services;

use Orion\Modules\User\Models\UserResource;
use Orion\Modules\Spacecraft\Models\Spacecraft;

class UserResourceService
{
    public function getUserResources($userId)
    {
        $userResources = UserResource::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();

        return $userResources;
    }
}
