<?php

namespace Orion\Modules\Rebel\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Rebel\Models\RebelResource;
use Orion\Modules\Rebel\Models\RebelSpacecraft;
use Orion\Modules\Rebel\Repositories\RebelRepository;

readonly class RebelService
{

    public function __construct(
        private readonly RebelRepository $rebelRepository
    ) {
    }

    // Add service logic here
    public function getAllRebels()
    {
        return $this->rebelRepository->getAllRebels();
    }

    public function findRebelById(int $id)
    {
        return $this->rebelRepository->findRebelById($id);
    }

    public function findRebelByName(string $name)
    {
        return $this->rebelRepository->findRebelByName($name);
    }

    public function findRebelByFaction(string $faction)
    {
        return $this->rebelRepository->findRebelByFaction($faction);
    }

    public function getAvailableSpacecraftsByIdWithDetails(int $id): Collection
    {
        return $this->getAllSpacecraftsByIdWithDetails($id)
            ->filter(fn($ship) => ($ship->count - $ship->locked_count) > 0)
            ->map(function($ship) {
                $ship->available_count = $ship->count - $ship->locked_count;
                return $ship;
            });
    }

    public function getAllSpacecraftsByIdWithDetails(int $id, ?Collection $filteredNames = null): Collection
    {
        $query = RebelSpacecraft::with('details')
            ->where('rebel_id', $id);
        
        if ($filteredNames) {
            $query->whereHas('details', function ($subquery) use ($filteredNames) {
                $subquery->whereIn('name', $filteredNames->keys());
            });
        }
        
        return $query->orderBy('id', 'asc')->get();
    }

    public function updateSpacecraftsCount(int $id, Collection $spacecrafts): void
    {
        DB::transaction(function () use ($id, $spacecrafts) {
            $spacecrafts->each(fn($spacecraft) =>
                RebelSpacecraft::where('rebel_id', $id)
                    ->where('id', $spacecraft->id)
                    ->update(['count' => $spacecraft->count])
            );
        });
    }

    public function updateLastInteraction(int $id): void
    {
        $this->rebelRepository->updateLastInteraction($id);
    }

}
