<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Asteroid\Services\AsteroidExplorer;
use Orion\Modules\User\Models\UserResource;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\User\Services\UserResourceService;

class CombatPlunderService
{
    public function __construct(
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService
    ) {
    }

    /**
     * Führt die Plünderung von Ressourcen nach einem gewonnenen Kampf durch
     */
    public function plunderResources(User $attacker, User $defender, array $attackerSpacecrafts): array
    {
        $formattedSpacecrafts = $this->formatSpacecraftsForCalculation($attackerSpacecrafts);
        $totalCargoCapacity = $this->calculateTotalCargoCapacity($attacker, $formattedSpacecrafts);
        $defenderResources = $this->userResourceService->getAllUserResourcesByUserId($defender->id);
        
        // Berechne verfügbare Ressourcen und Restressourcen
        $resourcesAvailable = [];
        $remainingResources = [];
        
        foreach ($defenderResources as $userResource) {
            if ($userResource->amount > 0) {
                $maxPlunder = floor($userResource->amount * 0.8); // 80% können geplündert werden
                $resourcesAvailable[$userResource->resource_id] = $maxPlunder;
                $remainingResources[$userResource->resource_id] = $userResource->amount - $maxPlunder;
            }
        }
        
        // Prüfe, ob die Gesamtmenge die Ladekapazität übersteigt
        $totalAvailable = array_sum($resourcesAvailable);
        $extractionRatio = $totalAvailable > $totalCargoCapacity ? $totalCargoCapacity / $totalAvailable : 1;
        
        // Berechne die tatsächlich zu transferierenden Ressourcen
        $resourcesExtracted = [];
        foreach ($resourcesAvailable as $resourceId => $amount) {
            $extractedAmount = floor($amount * $extractionRatio);
            if ($extractedAmount > 0) {
                $resourcesExtracted[$resourceId] = $extractedAmount;
                // Aktualisiere die verbleibenden Ressourcen des Verteidigers
                $remainingResources[$resourceId] = $defenderResources->firstWhere('resource_id', $resourceId)->amount - $extractedAmount;
            }
        }
        
        // Transaktionale Durchführung der Ressourcenänderungen
        DB::transaction(function () use ($attacker, $defender, $resourcesExtracted, $remainingResources) {
            $this->updateAttackerResources($attacker, $resourcesExtracted);
            $this->updateDefenderResources($defender, $remainingResources);
        });
        
        return $resourcesExtracted;
    }
    
    private function formatSpacecraftsForCalculation(array $spacecrafts): array
    {
        $formatted = [];
        foreach ($spacecrafts as $spacecraft) {
            $formatted[$spacecraft['name']] = $spacecraft['count'];
        }
        
        return $formatted;
    }
    
    /**
     * Berechnet die Gesamtladekapazität der Raumschiffe
     */
    private function calculateTotalCargoCapacity(User $user, array $spacecrafts): int
    {
        $totalCargoCapacity = 0;
        $spacecraftsWithDetails = $this->asteroidExplorer->getSpacecraftsWithDetails($user, collect($spacecrafts));
        
        foreach ($spacecraftsWithDetails as $spacecraft) {
            $amountOfSpacecrafts = $spacecrafts[$spacecraft->details->name];
            $totalCargoCapacity += $amountOfSpacecrafts * $spacecraft->cargo;
        }
        
        return $totalCargoCapacity;
    }
    
    /**
     * Aktualisiert die Ressourcen des Angreifers nach dem Plündern
     */
    private function updateAttackerResources(User $attacker, array $resourcesExtracted): void
    {
        $userStorageAttribute = $this->userAttributeService->getSpecificUserAttribute($attacker->id, 'storage_capacity');
        $storageCapacity = $userStorageAttribute->attribute_value;
        
        foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
            $userResource = UserResource::firstOrNew([
                'user_id' => $attacker->id,
                'resource_id' => $resourceId,
            ]);
            
            $availableStorage = $storageCapacity - $userResource->amount;
            $amountToAdd = min($extractedAmount, $availableStorage);
            
            $userResource->amount += $amountToAdd;
            $userResource->save();
        }
    }
    
    /**
     * Aktualisiert die Ressourcen des Verteidigers nach dem Plündern
     */
    private function updateDefenderResources(User $defender, array $remainingResources): void
    {
        foreach ($remainingResources as $resourceId => $remainingAmount) {
            $userResource = UserResource::where('user_id', $defender->id)
                ->where('resource_id', $resourceId)
                ->first();
            
            if ($userResource) {
                $userResource->amount = $remainingAmount;
                $userResource->save();
            }
        }
    }
}
