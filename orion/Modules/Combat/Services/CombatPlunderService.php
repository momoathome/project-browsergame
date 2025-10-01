<?php

namespace Orion\Modules\Combat\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\User\Models\UserResource;
use Orion\Modules\Rebel\Models\RebelResource;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\Asteroid\Services\AsteroidExplorer;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Rebel\Services\RebelResourceService;
use Orion\Modules\Building\Services\BuildingEffectService;


class CombatPlunderService
{
    public function __construct(
        private readonly AsteroidExplorer $asteroidExplorer,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService,
        private readonly RebelResourceService $rebelResourceService,
    ) {
    }

    /**
     * Führt die Plünderung von Ressourcen nach einem gewonnenen Kampf durch
     */
    public function plunderResources(User $attacker, User|Rebel $defender, Collection $attackerSpacecrafts): Collection
    {
        $isRebel = $defender instanceof Rebel;

        // Plünder-Schutz: User = Gebäude, Rebel = fix
        $plunderProtectionAmount = 100;
        if (!$isRebel) {
            $warehouseBuilding = Building::where('user_id', $defender->id)
                ->whereHas('details', function ($query) {
                    $query->where('name', BuildingType::WAREHOUSE->value);
                })
                ->first();

            if ($warehouseBuilding) {
                $extra = app(BuildingEffectService::class)->getEffects('Warehouse', $warehouseBuilding->level);
                $plunderProtectionAmount = $extra['resource_shielding'] ?? 1;
            }
        }

        $formattedSpacecrafts = $this->formatSpacecraftsForCalculation($attackerSpacecrafts);
        $totalCargoCapacity = $this->calculateTotalCargoCapacity($attacker, $formattedSpacecrafts);

        // Ressourcen holen
        if ($isRebel) {
            $defenderResources = $this->rebelResourceService->getAllRebelResourcesById($defender->id);
        } else {
            $defenderResources = $this->userResourceService->getAllUserResourcesByUserId($defender->id);
        }

        // Berechne verfügbare Ressourcen und Restressourcen
        $resourcesAvailable = collect();
        $remainingResources = collect();
        
        $defenderResources->each(function ($userResource) use ($resourcesAvailable, $remainingResources, $plunderProtectionAmount) {
            if ($userResource->amount > 0) {
                $maxPlunder = floor($userResource->amount * 0.8); // 80% können geplündert werden
        
                // Für Ressourcen 1,2,3,4 mindestens den Schutzwert behalten
                if (in_array($userResource->resource_id, [1, 2, 3, 4])) {
                    $maxPlunder = min($maxPlunder, max(0, $userResource->amount - $plunderProtectionAmount));
                }
        
                $resourcesAvailable->put($userResource->resource_id, $maxPlunder);
                $remainingResources->put($userResource->resource_id, $userResource->amount - $maxPlunder);
            }
        });

        // Prüfe, ob die Gesamtmenge die Ladekapazität übersteigt
        $totalAvailable = $resourcesAvailable->sum();
        $extractionRatio = $totalAvailable > $totalCargoCapacity ? $totalCargoCapacity / $totalAvailable : 1;

        // Berechne die tatsächlich zu transferierenden Ressourcen
        $resourcesExtracted = collect();
        $resourcesAvailable->each(function ($amount, $resourceId) use ($defenderResources, $extractionRatio, $resourcesExtracted, $remainingResources) {
            $extractedAmount = floor($amount * $extractionRatio);
            if ($extractedAmount > 0) {
                $resourcesExtracted->put($resourceId, $extractedAmount);
                // Aktualisiere die verbleibenden Ressourcen des Verteidigers
                $remainingResources->put($resourceId, $defenderResources->firstWhere('resource_id', $resourceId)->amount - $extractedAmount);
            }
        });

        // Transaktionale Durchführung der Ressourcenänderungen
        DB::transaction(function () use ($attacker, $defender, $resourcesExtracted, $remainingResources, $isRebel) {
            $this->updateAttackerResources($attacker, $resourcesExtracted);
            $this->updateDefenderResources($defender, $remainingResources, $isRebel);
        });

        return $resourcesExtracted;
    }

    private function formatSpacecraftsForCalculation(Collection $spacecrafts): Collection
    {
        return $spacecrafts->mapWithKeys(function ($spacecraft) {
            return [$spacecraft->details->name => $spacecraft->count];
        });
    }

    /**
     * Berechnet die Gesamtladekapazität der Raumschiffe
     */
    private function calculateTotalCargoCapacity(User $user, Collection $spacecrafts): int
    {
        $spacecraftsWithDetails = $this->asteroidExplorer->getSpacecraftsWithDetails($user, $spacecrafts);

        return $spacecraftsWithDetails->reduce(function ($totalCargoCapacity, $spacecraft) use ($spacecrafts) {
            $amountOfSpacecrafts = $spacecrafts->get($spacecraft->details->name);
            return $totalCargoCapacity + ($amountOfSpacecrafts * $spacecraft->cargo);
        }, 0);
    }

    /**
     * Aktualisiert die Ressourcen des Angreifers nach dem Plündern
     */
    private function updateAttackerResources(User $attacker, Collection $resourcesExtracted): void
    {
        $userStorageAttribute = $this->userAttributeService->getSpecificUserAttribute($attacker->id, UserAttributeType::STORAGE);
        $storageCapacity = $userStorageAttribute->attribute_value;

        $resourcesExtracted->each(function ($extractedAmount, $resourceId) use ($attacker, $storageCapacity) {
            $userResource = $this->userResourceService->getSpecificUserResource($attacker->id, $resourceId);

            $availableStorage = $storageCapacity - $userResource->amount;
            $amountToAdd = round(min($extractedAmount, $availableStorage));

            $userResource->amount += $amountToAdd;
            $userResource->save();
        });
    }

    /**
     * Aktualisiert die Ressourcen des Verteidigers nach dem Plündern
     */
    private function updateDefenderResources(User|Rebel $defender, Collection $remainingResources, bool $isRebel): void
    {
        $remainingResources->each(function ($remainingAmount, $resourceId) use ($defender, $isRebel) {

            if ($isRebel) {
                $defenderResource = $this->rebelResourceService->getSpecificRebelResource($defender->id, $resourceId);
            } else {
                $defenderResource = $this->userResourceService->getSpecificUserResource($defender->id, $resourceId);
            }

            if ($defenderResource) {
                $defenderResource->amount = round(max(0, $remainingAmount));
                $defenderResource->save();
            }
        });

        if (!$isRebel) {
            broadcast(new UpdateUserResources($defender));
        }
    }
}
