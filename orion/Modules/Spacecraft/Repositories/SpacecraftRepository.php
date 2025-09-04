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
            $spacecrafts->each(function ($spacecraft) use ($userId) {
                $this->findSpacecraftById($spacecraft->id, $userId)->update([
                    'count' => $spacecraft->count
                ]);
            });
        });
    }

    public function lockSpacecrafts($user, Collection $filteredSpacecrafts): bool
    {
        return $this->updateSpacecraftLockedCount($user->id, $filteredSpacecrafts, true);
    }
    
    public function freeSpacecrafts($user, Collection $filteredSpacecrafts): bool
    {
        return $this->updateSpacecraftLockedCount($user->id, $filteredSpacecrafts, false);
    }
    
    public function updateSpacecraftLockedCount(int $userId, Collection $filteredSpacecrafts, bool $increment = false): bool
    {
        return DB::transaction(function () use ($userId, $filteredSpacecrafts, $increment) {
            foreach ($filteredSpacecrafts as $type => $amount) {
                $spacecraft = Spacecraft::where('user_id', $userId)
                    ->whereHas('details', fn($q) => $q->where('name', $type))
                    ->lockForUpdate()
                    ->first();
    
                if (!$spacecraft) {
                    throw new \Exception("Spacecraft $type not found");
                }
    
                if ($increment) {
                    // Lock
                    if (($spacecraft->count - $spacecraft->locked_count) < $amount) {
                        throw new \Exception("Not enough $type available");
                    }
                    $spacecraft->locked_count += $amount;
                } else {
                    // Unlock
                    $spacecraft->locked_count = max(0, $spacecraft->locked_count - $amount);
                }
                $spacecraft->save();
            }
            return true;
        });
    }
}
