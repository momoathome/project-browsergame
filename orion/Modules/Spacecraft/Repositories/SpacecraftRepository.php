<?php

namespace Orion\Modules\Spacecraft\Repositories;

use Orion\Modules\Spacecraft\Models\Spacecraft;

readonly class SpacecraftRepository
{
    public function getAllSpacecraftsByUserId(int $userId)
    {
        return Spacecraft::where('user_id', $userId)->get();
    }

    public function getAllSpacecraftsByUserIdWithDetails(int $userId)
    {
        return Spacecraft::with('details')
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getAllSpacecraftsByUserIdWithDetailsAndResources(int $userId)
    {
        return Spacecraft::with('details', 'resources')
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }

    
}
