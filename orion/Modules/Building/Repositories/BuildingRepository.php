<?php

namespace Orion\Modules\Building\Repositories;

use Illuminate\Support\Collection;
use Orion\Modules\Building\Models\Building;

readonly class BuildingRepository
{
    public function getAllBuildingsByUserIdWithDetailsAndResources(int $userId): Collection
    {
        return Building::with('details', 'resources')
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getAllBuildingsByUserId(int $userId): Collection
    {
        return Building::where('user_id', $userId)
            ->get();
    }

    public function getOneBuildingByUserId(int $buildingId, int $userId)
    {
        return Building::where('id', $buildingId)
            ->where('user_id', $userId)
            ->first();
    }
}
