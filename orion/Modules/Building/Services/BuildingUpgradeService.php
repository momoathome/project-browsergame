<?php

namespace Orion\Modules\Building\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Building\Enums\BuildingEffectType;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Building\Services\BuildingCostCalculator;
use Orion\Modules\Building\Services\BuildingEffectCalculator;
use Orion\Modules\Resource\Exceptions\InsufficientResourceException;

class BuildingUpgradeService
{
    public function __construct(
        private readonly BuildingService $buildingService,
        private readonly BuildingCostCalculator $costCalculator,
        private readonly BuildingEffectCalculator $effectCalculator,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService,
        private readonly ActionQueueService $queueService,
        private readonly ResourceService $resourceService
    ) {
    }

    public function startBuildingUpgrade(User $user, Building $building): array
    {
        try {
            $currentCosts = $this->getBuildingUpgradeCosts($building);

            // Prüfe, ob der User genügend Ressourcen hat
            $this->userResourceService->validateUserHasEnoughResources($user->id, $currentCosts);

            // Führe das Upgrade durch
            DB::transaction(function () use ($user, $building, $currentCosts) {
                // Ressourcen abziehen
                $this->userResourceService->decrementUserResources($user, $currentCosts);

                // Upgrade zur Queue hinzufügen
                $this->addBuildingUpgradeToQueue($user->id, $building);
            });

            $user = $this->userService->find($user->id);
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
            \Log::error("Error occurred while starting building upgrade:", [
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

    private function getBuildingUpgradeCosts(Building $building): Collection
    {
        return $building->resources()->get()->map(function ($resource) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'amount' => $resource->pivot->amount
            ];
        })->keyBy('id');
    }

    private function addBuildingUpgradeToQueue(int $userId, Building $building): void
    {
        $building_produce_speed = config('game.core.building_produce_speed');
        $this->queueService->addToQueue(
            $userId,
            QueueActionType::ACTION_TYPE_BUILDING,
            $building->id,
            $building->build_time / $building_produce_speed,
            [
                'building_name' => $building->details->name,
                'current_level' => $building->level,
                'next_level' => $building->level + 1,
            ]
        );
    }

    public function upgradeBuilding(Building $building)
    {
        $costs = $this->calculateUpgradeCosts($building);

        return DB::transaction(function () use ($building, $costs) {
            // Gebäude aktualisieren
            $building->level += 1;
            $building->effect_value = $this->calculateNewEffectValue($building);
            $building->build_time = $this->calculateBuildTime($building);
            $building->save();

            // Neue Kosten für das nächste Level speichern
            $this->updateBuildingCosts($building, $costs);

            return $building;
        });
    }

    public function calculateUpgradeCosts(Building $building): Collection
    {
        $costs = $this->costCalculator->calculateUpgradeCost($building) ?? [];
        return collect($costs);
    }

    private function calculateNewEffectValue(Building $building): float
    {
        return $this->effectCalculator->calculateEffectValue($building);
    }

    private function calculateBuildTime(Building $building): float
    {
        // Bauzeit je nach Level erhöhen
        $buildTimeMultiplier = config('game.building_progression.build_time_multiplier', 1.35);
        return floor(60 * pow($buildTimeMultiplier, $building->level - 1));
    }

    private function updateBuildingCosts(Building $building, Collection $costs): void
    {
        // Bestehende Verknüpfungen löschen
        $building->resources()->detach();

        $resources = $this->resourceService->getResourceIdMapping();
        $resourceData = collect();

        $costs->each(function ($cost) use ($resources, &$resourceData) {
            if (isset($resources[$cost['resource_name']])) {
                $resourceId = $resources[$cost['resource_name']];
                $resourceData->put($resourceId, ['amount' => $cost['amount']]);
            }
        });

        // Neue Verknüpfungen mit Pivot-Daten erstellen
        $building->resources()->attach($resourceData->toArray());
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
            \Log::error("Fehler beim Gebäude-Upgrade: " . $e->getMessage(), [
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
        $effectType = $effectConfig['type'] ?? BuildingEffectType::ADDITIVE;
        $updatedAttributes = collect();

        // Debug-Informationen hinzufügen
        \Log::debug("Updating user attributes for building", [
            'user_id' => $userId,
            'building_id' => $building->id,
            'building_name' => $building->details->name,
            'building_level' => $building->level,
            'effect_value' => $building->effect_value
        ]);

        // Wenn keine Attribute definiert sind, frühzeitig beenden
        if (empty($effectAttributeNames)) {
            return $updatedAttributes;
        }

        collect($effectAttributeNames)->each(function ($attributeNameStr) use ($userId, $building, $effectType, &$updatedAttributes) {
            $attributeName = UserAttributeType::tryFrom($attributeNameStr);

            // Falls die Umwandlung fehlschlägt, überspringen
            if ($attributeName === null) {
                \Log::warning("Ungültiger Attributtyp: {$attributeNameStr}", [
                    'user_id' => $userId,
                    'building_id' => $building->id
                ]);
                return;
            }

            // Den vorberechneten effect_value direkt übernehmen
            $valueToApply = ceil($building->effect_value);

            // Attribut aktualisieren - replace=true, damit der Wert ersetzt und nicht addiert wird
            $updatedAttribute = $this->userAttributeService->updateUserAttribute(
                $userId,
                $attributeName,
                $valueToApply,
                false,  // nicht multiplizieren
                true    // ersetzen
            );

            if ($updatedAttribute) {
                $updatedAttributes->put($attributeNameStr, [
                    'name' => $attributeNameStr,
                    'new_value' => $updatedAttribute->attribute_value,
                    'effect_applied' => $valueToApply,
                    'effect_type' => $effectType->value
                ]);
            } else {
                \Log::warning("Attribut {$attributeNameStr} konnte nicht aktualisiert werden", [
                    'user_id' => $userId,
                    'building_id' => $building->id,
                    'effect_value' => $valueToApply
                ]);
            }
        });

        return $updatedAttributes;
    }

}
