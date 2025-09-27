<?php

namespace Orion\Modules\Rebel\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\Rebel\Models\RebelResource;
use Orion\Modules\Rebel\Models\RebelSpacecraft;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Rebel\Services\RebelDifficultyService;

class RebelResourceService
{
    public function __construct(
        private readonly SpacecraftService $spacecraftService,
        private readonly RebelDifficultyService $difficultyService,
    ) {
    }

    public function generateResources(Rebel $rebel, $ticks = null, int $globalDifficulty = 0)
    {
        // Falls globalDifficulty nicht mitgegeben → selbst berechnen
        $globalDifficulty = $globalDifficulty ?? $this->difficultyService->calculateGlobalDifficulty();

        $difficulty = $rebel->difficulty_level + $globalDifficulty;
        $rate = config('game.rebels.resources_per_tick', 10) * $difficulty; // Ressourcen pro Tick

        $last = $rebel->last_interaction ?? now();
        $now = now();
        $tickMinutes = config('game.rebels.tick_interval_minutes', 10); // Minuten pro Tick
        
        $minutes = $last->diffInMinutes($now); 
        $ticks = $ticks ?? max(0, floor($minutes / $tickMinutes));
        
        if ($ticks < 1) {
            return;
        }

        $phase = $this->getGamePhase();
        $faction = $rebel->faction;
        $ratios = $this->getScaledRatios($faction, $phase);

        foreach (config('game.resources.resources') as $resource) {
            $name = $resource['name'] ?? null;
            if (!$name) continue;

            $ratio = $ratios[$name] ?? 0.0;
            $amount = round($rate * $ticks * $ratio);

            if ($amount < 1) continue;

            $resourceId = $this->getResourceId($name);
            $rebelResource = RebelResource::where('rebel_id', $rebel->id)
                ->where('resource_id', $resourceId)
                ->first();

            $currentAmount = $rebelResource?->amount ?? 0;

            // Cap über DifficultyService
            $cap = $this->difficultyService->getResourceCap($rebel, $globalDifficulty);

            $amountToAdd = min($amount, max(0, $cap - $currentAmount));
            if ($amountToAdd < 1) continue;

            if ($rebelResource) {
                $rebelResource->increment('amount', $amountToAdd);
            } else {
                RebelResource::create([
                    'rebel_id' => $rebel->id,
                    'resource_id' => $resourceId,
                    'amount' => $amountToAdd,
                ]);
            }
        }

        $rebel->last_interaction = $now;
        $rebel->save();
    }
    
    // TODO: write gamephase in DB or implement Global difficulty
    public function getGamePhase()
    {
        $avgMiner = $this->spacecraftService->getAllSpacecraftsByType('Miner')->avg('count');

        if ($avgMiner < 15) return 'early';
        if ($avgMiner < 75) return 'mid';
        return 'late';
    }
    
    protected function getScaledRatios($faction, $phase)
    {
        $baseRatios = config('game.rebels.resource_ratios')[$faction] ?? [];

        // Hole die Ressourcenkategorien aus der Market-Config
        $resourceCategories = [];
        foreach (config('game.market.markets') as $market) {
            if (isset($market['resource_name'], $market['category'])) {
                $resourceCategories[$market['resource_name']] = $market['category'];
            }
        }

        $allowedCategories = match($phase) {
            'early' => ['low_value', 'medium_value'],
            'mid'   => ['low_value', 'medium_value', 'high_value'],
            'late'  => ['low_value', 'medium_value', 'high_value', 'extreme_value'],
            default => ['low_value'],
        };

        return collect($baseRatios)
            ->map(fn($ratio, $name) => in_array($resourceCategories[$name] ?? 'low_value', $allowedCategories) ? $ratio : 0)
            ->toArray();
    }

    public function getResourceId($name)
    {
        // Hole die Resource-ID anhand des Namens
        return Resource::where('name', $name)->value('id');
    }

    public function getRebelResource(Rebel $rebel, array $cost)
    {
        return RebelResource::where('rebel_id', $rebel->id)
                ->where('resource_id', $this->getResourceId($cost['resource_name']))
                ->first();
    }

    public function getAllRebelResourcesById(int $id): Collection
    {
        return RebelResource::with('resource')
            ->where('rebel_id', $id)
            ->orderBy('resource_id', 'asc')
            ->get();
    }

    public function getSpecificRebelResource(int $id, int $resourceId): RebelResource|null
    {
        return RebelResource::with('resource')
            ->where('rebel_id', $id)
            ->where('resource_id', $resourceId)
            ->first();
    }
    
    /**
     * Zieht Ressourcen für ein Schiff ab.
     *
     * @param Rebel $rebel
     * @param array $costs
     * @param int $count
     * @return void
     */
    public function spendResources(Rebel $rebel, array $costs, int $count): void
    {
        foreach ($costs as $cost) {
            RebelResource::where('rebel_id', $rebel->id)
                ->where('resource_id', $this->getResourceId($cost['resource_name']))
                ->decrement('amount', $cost['amount'] * $count);
        }
    }

}
