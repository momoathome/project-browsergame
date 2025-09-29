<?php

namespace Orion\Modules\Spacecraft\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Spacecraft\Models\Spacecraft;

readonly class SpacecraftRepository
{

    public function findSpacecraftById(int $id, int $userId)
    {
        return Spacecraft::where('user_id', $userId)
            ->where('id', $id)
            ->first();
    }
    
    public function getAllSpacecraftsByUserId(int $userId): Collection
    {
        return Spacecraft::where('user_id', $userId)->get();
    }

    public function getAllSpacecraftsByUserIdWithDetails(int $userId, ?Collection $filteredNames = null): Collection
    {
        $query = Spacecraft::with('details')
            ->where('user_id', $userId);
        
        if ($filteredNames) {
            $query->whereHas('details', function ($subquery) use ($filteredNames) {
                $subquery->whereIn('name', $filteredNames->keys());
            });
        }
        
        return $query->orderBy('id', 'asc')->get();
    }

    public function getAllSpacecraftsByUserIdWithDetailsAndResources(int $userId): Collection
    {
        return Spacecraft::with('details', 'resources')
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function updateSpacecraftsCount(int $userId, Collection $spacecrafts): void
    {
        DB::transaction(function () use ($userId, $spacecrafts) {
            $spacecrafts->each(fn($spacecraft) =>
                Spacecraft::where('user_id', $userId)
                    ->where('id', $spacecraft->id)
                    ->update(['count' => $spacecraft->count])
            );
        });
    }

    // get all spacecrafts from all users where spacecraft.details.type = $type
    public function getAllSpacecraftsByType(string $type): Collection
    {
        return Spacecraft::whereHas('details', function ($query) use ($type) {
            $query->where('type', $type);
        })->get();
    }
}
