<?php

namespace Orion\Modules\User\Repositories;

use Illuminate\Support\Collection;
use Orion\Modules\User\Models\UserAttribute;
use Orion\Modules\User\Enums\UserAttributeType;

readonly class UserAttributeRepository
{
    // Add repository logic here
    public function getAllUserAttributesByUserId(int $userId): Collection
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

    public function getTotalAttributeValueByType(UserAttributeType $attributeType): int
    {
        return UserAttribute::where('attribute_name', $attributeType->value)
            ->sum('attribute_value');
    }

    public function getInfluenceOfAllUsers(): Collection
    {
        return UserAttribute::query()
            ->select('user_attributes.user_id', 'user_attributes.attribute_value', 'users.name')
            ->join('users', 'users.id', '=', 'user_attributes.user_id')
            ->where('attribute_name', UserAttributeType::INFLUENCE->value)
            ->orderBy('attribute_value', 'desc')
            ->get();
    }
}
