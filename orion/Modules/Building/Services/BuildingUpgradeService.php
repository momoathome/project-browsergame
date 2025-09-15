<?php

namespace Orion\Modules\Building\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use Orion\Modules\Building\Enums\BuildingEffectType;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Building\Services\BuildingProgressionService;
use Orion\Modules\User\Exceptions\InsufficientResourceException;
use Orion\Modules\Influence\Services\InfluenceService;

class BuildingUpgradeService
{
    public function __construct(
        private readonly BuildingService $buildingService,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService,
        private readonly BuildingProgressionService $buildingProgressionService,
        private readonly ActionQueueService $queueService,
        private readonly ResourceService $resourceService,
        private readonly InfluenceService $influenceService
    ) {
    }

    public function startBuildingUpgrade(User $user, Building $building): array
    {
        $coreBuilding = Building::where('user_id', $user->id)
            ->whereHas('details', function ($query) {
                $query->where('name', BuildingType::CORE->value);
            })
            ->first();

        $queuedUpgrades = $this->queueService->getQueuedUpgrades($user->id, $building->id, QueueActionType::ACTION_TYPE_BUILDING)->count();
        $targetLevel = $building->level + $queuedUpgrades + 1;

        if ($coreBuilding && $targetLevel > $coreBuilding->level && $building->details->name !== BuildingType::CORE->value) {
            return [
                'success' => false,
                'message' => 'You cannot upgrade buildings beyond the Core building level.'
            ];
        }

        try {
            $currentCosts = $this->buildingProgressionService->calculateUpgradeCost($building, $targetLevel);
            // Prüfe, ob der User genügend Ressourcen hat
            $this->userResourceService->validateUserHasEnoughResources($user->id, $currentCosts);

            // Führe das Upgrade durch
            DB::transaction(function () use ($user, $building, $currentCosts) {
                // Ressourcen abziehen
                $this->userResourceService->decrementUserResources($user, $currentCosts);

                // Upgrade zur Queue hinzufügen
                $this->addBuildingUpgradeToQueue($user->id, $building);
            });

            broadcast(new UpdateUserResources($user));
            return [
                'success' => true,
                'message' => "Upgrade of {$building->details->name} successfully started"
            ];
        } catch (InsufficientResourceException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error("Error occurred while starting building upgrade:", [
                'user_id' => $user->id,
                'building_id' => $building->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error occurred while starting building upgrade: ' . $e->getMessage()
            ];
        }
    }

    public function cancelBuildingUpgrade(User $user, Building $building): array
    {
        $queueEntries = $this->queueService->getQueuesFromUserByType($user->id, QueueActionType::ACTION_TYPE_BUILDING)
            ->where('target_id', $building->id);
    
        if ($queueEntries->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active or pending upgrade found for this building.'
            ];
        }
    
        try {
            DB::transaction(function () use ($user, $building, $queueEntries) {
                foreach ($queueEntries as $queueEntry) {
                    $queuedUpgrades = $this->queueService->getQueuedUpgrades($user->id, $building->id, QueueActionType::ACTION_TYPE_BUILDING)->count();
                    $targetLevel = $building->level + $queuedUpgrades;
    
                    $currentCosts = collect($this->buildingProgressionService->calculateUpgradeCost($building, $targetLevel));
                    $refundFactor = $queueEntry->status === QueueStatusType::STATUS_PENDING ? 1.0 : 0.8;
    
                    $refundCosts = $currentCosts->map(function ($cost) use ($refundFactor) {
                        return [
                            'id' => $cost['id'],
                            'name' => $cost['name'],
                            'amount' => (int) round($cost['amount'] * $refundFactor)
                        ];
                    })->keyBy('id');
    
                    foreach ($refundCosts as $cost) {
                        $this->userResourceService->addResourceAmount($user, $cost['id'], $cost['amount']);
                    }
    
                    $this->queueService->deleteFromQueue($queueEntry->id);
                }
            });
    
            broadcast(new UpdateUserResources($user));
            return [
                'success' => true,
                'message' => "All upgrades for {$building->details->name} have been cancelled. Resources have been refunded (pending: 100%, active: 80%)."
            ];
        } catch (\Exception $e) {
            Log::error("Error occurred while cancelling building upgrades:", [
                'user_id' => $user->id,
                'building_id' => $building->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return [
                'success' => false,
                'message' => 'Error occurred while cancelling building upgrades: ' . $e->getMessage()
            ];
        }
    }

    private function addBuildingUpgradeToQueue(int $userId, Building $building): void
    {
        $queuedUpgrades = $this->queueService->getQueuedUpgrades($userId, $building->id, QueueActionType::ACTION_TYPE_BUILDING)->count();
        $targetLevel = $building->level + $queuedUpgrades + 1;

        $core_upgrade_speed = $this->userAttributeService->getSpecificUserAttribute($userId, UserAttributeType::UPGRADE_SPEED);
        $building_produce_speed = config('game.core.building_produce_speed');

        $upgrade_multiplier = $core_upgrade_speed ? $core_upgrade_speed->attribute_value : 1;
        $build_time = $this->buildingProgressionService->calculateBuildTime($building, $targetLevel);
        $effective_build_time = floor(($build_time / $upgrade_multiplier) / $building_produce_speed);

        $this->queueService->addToQueue(
            $userId,
            QueueActionType::ACTION_TYPE_BUILDING,
            $building->id,
            $effective_build_time,
            [
                'building_name' => $building->details->name,
                'current_level' => $targetLevel - 1,
                'next_level' => $targetLevel,
                'duration' => $effective_build_time
            ]
        );
    }

    public function upgradeBuilding(Building $building)
    {
        return DB::transaction(function () use ($building) {
            $building->level += 1;
            $building->effect_value = $this->buildingProgressionService->calculateEffectValue($building);
            $building->build_time = $this->buildingProgressionService->calculateBuildTime($building, $building->level);
            $building->save();

            return $building;
        });
    }

    public function completeUpgrade(int $buildingId, int $userId): array
    {
        // Gebäude abrufen mit Validierung
        $building = $this->buildingService->getOneBuildingByUserId($buildingId, $userId);

        if (!$building) {
            return [
                'success' => false,
                'message' => 'Gebäude nicht gefunden oder gehört nicht diesem Benutzer'
            ];
        }

        try {
            $result = DB::transaction(function () use ($building, $userId) {
                // 1. Upgrade durchführen
                $upgradedBuilding = $this->upgradeBuilding($building);

                if (!$upgradedBuilding) {
                    throw new \Exception("Fehler beim Upgrade des Gebäudes");
                }

                // 2. BuildingType bestimmen
                $buildingType = BuildingType::tryFrom($upgradedBuilding->details->name);

                if (!$buildingType) {
                    throw new \Exception("Ungültiger Gebäudetyp: " . $upgradedBuilding->details->name);
                }

                // 3. Benutzerattribute aktualisieren
                $attributeUpdates = $this->updateUserAttributesForBuilding($userId, $upgradedBuilding, $buildingType);

                $this->influenceService->handleBuildingUpgradeCompleted($userId, $this->buildingProgressionService->calculateUpgradeCost($building, $building->level));

                return [
                    'success' => true,
                    'building' => $upgradedBuilding,
                    'updated_attributes' => $attributeUpdates
                ];
            });

            return [
                'success' => true,
                'message' => 'Gebäude-Upgrade erfolgreich abgeschlossen',
                'details' => $result
            ];
        } catch (\Exception $e) {
            Log::error("Fehler beim Gebäude-Upgrade: " . $e->getMessage(), [
                'building_id' => $buildingId,
                'user_id' => $userId,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Fehler beim Abschließen des Upgrades: ' . $e->getMessage()
            ];
        }
    }

    private function updateUserAttributesForBuilding(int $userId, Building $building, BuildingType $buildingType): Collection
    {
        $effectAttributeNames = $buildingType->getEffectAttributes();
        $effectConfig = $buildingType->getEffectConfiguration();
        $effectType = BuildingEffectType::tryFrom($effectConfig['type'] ?? 'additive') ?? BuildingEffectType::ADDITIVE;
        $updatedAttributes = collect();

        // Wenn keine Attribute definiert sind, frühzeitig beenden
        if (empty($effectAttributeNames)) {
            return $updatedAttributes;
        }

        collect($effectAttributeNames)->each(function ($attributeNameStr) use ($userId, $building, $effectType, &$updatedAttributes, $buildingType) {
            $attributeName = UserAttributeType::tryFrom($attributeNameStr);

            // Falls die Umwandlung fehlschlägt, überspringen
            if ($attributeName === null) {
                Log::warning("Ungültiger Attributtyp: {$attributeNameStr}", [
                    'user_id' => $userId,
                    'building_id' => $building->id
                ]);
                return;
            }

            if ($buildingType === BuildingType::LABORATORY) {
                $increment = $buildingType->getEffectConfiguration()['increment'] ?? 3;
                $valueToApply = $increment;
                $replace = false; // addieren statt ersetzen
            } else {
                $valueToApply = $building->effect_value;
                $replace = true; // ersetzen wie gehabt
            }

            // Attribut aktualisieren - replace=true, damit der Wert ersetzt und nicht addiert wird
            $updatedAttribute = $this->userAttributeService->updateUserAttribute(
                $userId,
                $attributeName,
                $valueToApply,
                false,      // nicht multiplizieren
                $replace    // ersetzen
            );

            if ($updatedAttribute) {
                $updatedAttributes->put($attributeNameStr, [
                    'name' => $attributeNameStr,
                    'new_value' => $updatedAttribute->attribute_value,
                    'effect_applied' => $valueToApply,
                    'effect_type' => $effectType->value
                ]);
            } else {
                Log::warning("Attribut {$attributeNameStr} konnte nicht aktualisiert werden", [
                    'user_id' => $userId,
                    'building_id' => $building->id,
                    'effect_value' => $valueToApply
                ]);
            }
        });

        return $updatedAttributes;
    }

}
