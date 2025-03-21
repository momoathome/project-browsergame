<?php

namespace Orion\Modules\Spacecraft\Repositories;

use Orion\Modules\Spacecraft\Models\Spacecraft;

readonly class SpacecraftRepository
{
    // Add repository logic here
    public function getAllSpacecraftsByUserId(int $userId)
    {
        return Spacecraft::with('details', 'resources')
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }
}
