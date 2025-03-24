<?php

namespace Orion\Modules\User\Repositories;

use Orion\Modules\User\Models\UserAttribute;
use Orion\Modules\User\Enums\UserAttributeType;

readonly class UserAttributeRepository
{
    // Add repository logic here
    public function getAllUserAttributesByUserId(int $userId)
    {
        return UserAttribute::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getSpecificUserAttribute(int $userId, UserAttributeType $attributeName)
    {
        return UserAttribute::where('user_id', $userId)
            ->where('attribute_name', $attributeName)
            ->first();
    }
}
