<?php

namespace App\Services;

use App\Models\UserAttribute;
use App\Models\Spacecraft;

class UserAttributeService
{
    public function getUserAttributes($userId)
    {
        $userAttributes = UserAttribute::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();

        $spacecrafts = Spacecraft::where('user_id', $userId)->get();

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
}
