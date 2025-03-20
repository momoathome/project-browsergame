<?php

namespace App\Services;

use App\Models\UserAttribute;
use App\Models\Spacecraft;

class UserAttributeService
{
    public function getUserAttributes($userId)
    {
        // Nur die Attribute des angegebenen Benutzers abrufen
        $userAttributes = UserAttribute::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();

        // calculate total spacecraft crew_limit
        $spacecrafts = Spacecraft::where('user_id', $userId)
            ->get();

        $totalCrewLimit = $spacecrafts->sum(function ($spacecraft) {
            return $spacecraft->count > 0 ? $spacecraft->crew_limit * $spacecraft->count : 0;
        });

        // Finde oder aktualisiere das total_units Attribut
        $totalUnitsAttribute = $userAttributes->where('attribute_name', 'total_units')->first();
        if ($totalUnitsAttribute) {
            $totalUnitsAttribute->attribute_value = $totalCrewLimit;
        }

        return $userAttributes;
    }
}
