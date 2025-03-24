<?php

namespace Orion\Modules\Spacecraft\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Spacecraft\Models\Spacecraft;

readonly class SpacecraftRepository
{

    public function findSpacecraftById(int $id)
    {
        return Spacecraft::find($id);
    }
    
    public function getAllSpacecraftsByUserId(int $userId)
    {
        return Spacecraft::where('user_id', $userId)->get();
    }

    public function getAllSpacecraftsByUserIdWithDetails(int $userId, ?Collection $filteredNames = null)
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

    public function getAllSpacecraftsByUserIdWithDetailsAndResources(int $userId)
    {
        return Spacecraft::with('details', 'resources')
            ->where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function lockSpacecrafts($user, Collection $filteredSpacecrafts): bool
    {
        return $this->updateSpacecraftCount($user->id, $filteredSpacecrafts, false);
    }
    
    public function freeSpacecrafts($user, Collection $filteredSpacecrafts): bool
    {
        return $this->updateSpacecraftCount($user->id, $filteredSpacecrafts, true);
    }
    
    private function updateSpacecraftCount(int $userId, Collection $filteredSpacecrafts, bool $increment = false): bool
    {
        return DB::transaction(function () use ($userId, $filteredSpacecrafts, $increment) {
            $spacecrafts = $this->getAllSpacecraftsByUserIdWithDetails($userId, $filteredSpacecrafts);
                
            foreach ($spacecrafts as $spacecraft) {
                $spacecraft->locked_count = $spacecraft->locked_count ?? 0;
                
                $changeAmount = $filteredSpacecrafts->has($spacecraft->details->name) ?
                    $filteredSpacecrafts->get($spacecraft->details->name) : 0;
                    
                if ($changeAmount <= 0) {
                    continue;
                }
                
                if ($increment) {
                    // Wenn wir Schiffe freigeben, reduzieren wir locked_count
                    $spacecraft->locked_count = max(0, $spacecraft->locked_count - $changeAmount);
                } else {
                    // Wenn wir Schiffe sperren, erhöhen wir locked_count
                    $changeAmount = min($changeAmount, $spacecraft->count);
                    $spacecraft->locked_count += $changeAmount;
                }
                
                // Sicherstellen, dass keine negativen Werte existieren
                $spacecraft->count = max(0, $spacecraft->count);
                $spacecraft->locked_count = max(0, $spacecraft->locked_count);
                
                $spacecraft->save();
            }
            
            return true;
        });
    }
    
}
