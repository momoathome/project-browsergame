<?php

namespace Orion\Modules\User\Services;

use Orion\Modules\User\Repositories\UserAttributeRepository;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class UserAttributeService
{
    public function __construct(
        private readonly UserAttributeRepository $userAttributeRepository,
        private readonly SpacecraftService $spacecraftService
    ) {
    }
    public function getAllUserAttributesByUserId($userId)
    {
        $userAttributes = $this->userAttributeRepository->getAllUserAttributesByUserId($userId);

        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserId($userId);

        // fill total units attribute
        $totalCrewLimit = $spacecrafts->sum(function ($spacecraft) {
            $totalCount = $spacecraft->count + ($spacecraft->locked_count ?? 0);
            return $totalCount > 0 ? $spacecraft->crew_limit * $totalCount : 0;
        });

        $totalUnitsAttribute = $userAttributes->where('attribute_name', 'total_units')->first();
        if ($totalUnitsAttribute) {
            $totalUnitsAttribute->attribute_value = $totalCrewLimit;
        }

        return $userAttributes;
    }

    public function getSpecificUserAttribute($userId, $attributeName)
    {
        return $this->userAttributeRepository->getSpecificUserAttribute($userId, $attributeName);
    }

    public function updateUserAttribute($userId, $attributeName, $value, $multiply = false, $replace = false)
    {
        $userAttribute = $this->getSpecificUserAttribute($userId, $attributeName);

        if ($userAttribute) {
            if ($multiply) {
                $userAttribute->attribute_value = round($userAttribute->attribute_value * $value);
            } else if ($replace) {
                $userAttribute->attribute_value = $value;
            } else {
                $userAttribute->attribute_value += $value;
            }
            $userAttribute->save();
        }
    }
}
