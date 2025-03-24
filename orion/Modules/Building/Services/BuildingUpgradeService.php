<?php

namespace Orion\Modules\Building\Services;

use Illuminate\Support\Facades\DB;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Building\Enums\BuildingType;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Resource\Services\ResourceService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\Building\Services\BuildingCostCalculator;
use Orion\Modules\Building\Services\BuildingEffectCalculator;
use Orion\Modules\Building\Enums\BuildingEffectType;

class BuildingUpgradeService
{
    public function __construct(
        private readonly BuildingService $buildingService,
        private readonly BuildingCostCalculator $costCalculator,
        private readonly BuildingEffectCalculator $effectCalculator,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService,
        private readonly QueueService $queueService,
        private readonly ResourceService $resourceService
    ) {
    }

    /**
     * Startet ein Gebäude-Upgrade, wenn alle Voraussetzungen erfüllt sind
     * 
     * @param int $userId
     * @param Building $building
     * @throws \Exception wenn Ressourcen nicht ausreichen
     */
    public function startBuildingUpgrade(int $userId, Building $building): void
    {
        $currentCosts = $this->getBuildingUpgradeCosts($building);

        // Prüfe, ob der User genügend Ressourcen hat
        $this->validateUserHasEnoughResources($userId, $currentCosts);

        // Führe das Upgrade in einer Transaktion durch
        DB::transaction(function () use ($userId, $building, $currentCosts) {
            // Ressourcen abziehen
            $this->decrementResourcesFromUser($userId, $currentCosts);

            // Upgrade zur Queue hinzufügen
            $this->addBuildingUpgradeToQueue($userId, $building);
        });
    }

    private function getBuildingUpgradeCosts(Building $building): array
    {
        return $building->resources()->get()->map(function ($resource) {
            // Statt Objekt ein einfaches Array zurückgeben
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'amount' => $resource->pivot->amount
            ];
        })->keyBy('id')->toArray();
    }

    private function validateUserHasEnoughResources(int $userId, array $requiredResources): void
    {
        $userResources = collect($this->userResourceService->getAllUserResourcesByUserId($userId))
            ->keyBy('resource_id');

        foreach ($requiredResources as $resourceId => $resourceCost) {
            $userResource = $userResources->get($resourceId);
            $requiredAmount = $resourceCost['amount'];

            if (!$userResource || $userResource->amount < $requiredAmount) {
                $resourceName = $resourceCost['name'] ?? 'Resource #' . $resourceId;
                throw new \Exception("Not enough {$resourceName}");
            }
        }
    }

    private function decrementResourcesFromUser(int $userId, array $requiredResources): void
    {
        foreach ($requiredResources as $resourceId => $resource) {
            $this->userResourceService->subtractResourceAmount($userId, $resourceId, $resource['amount']);
        }
    }

    private function addBuildingUpgradeToQueue(int $userId, Building $building): void
    {
        $this->queueService->addToQueue(
            $userId,
            QueueActionType::ACTION_TYPE_BUILDING,
            $building->id,
            $building->build_time,
            [
                'building_name' => $building->details->name,
                'current_level' => $building->level,
                'next_level' => $building->level + 1,
            ]
        );
    }

    public function calculateUpgradeCosts(Building $building): array
    {
        return $this->costCalculator->calculateUpgradeCost($building) ?? [];
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

    private function calculateNewEffectValue(Building $building): float
    {
        return $this->effectCalculator->calculateEffectValue($building);
    }

    private function calculateBuildTime(Building $building)
    {
        // Bauzeit je nach Level erhöhen
        return floor(60 * pow(1.2, $building->level - 1));
    }

    private function updateBuildingCosts(Building $building, $costs)
    {
        // Bestehende Verknüpfungen löschen
        $building->resources()->detach();

        $resources = $this->resourceService->getResourceIdMapping();
        $resourceData = [];

        foreach ($costs as $cost) {
            if (isset($resources[$cost['resource_name']])) {
                $resourceId = $resources[$cost['resource_name']];
                $resourceData[$resourceId] = ['amount' => $cost['amount']];
            }
        }

        // Neue Verknüpfungen mit Pivot-Daten erstellen
        $building->resources()->attach($resourceData);
    }

    /**
     * Schließt ein Gebäude-Upgrade ab und wendet die Effekte an
     * 
     * @param int $buildingId Die ID des zu aktualisierenden Gebäudes
     * @param int $userId Der Benutzer, dem das Gebäude gehört
     * @return array Statusmeldung mit Erfolg/Misserfolg und Details
     */
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
                
                if (empty($attributeUpdates)) {
                    // Kein Fehler, aber keine Attribute wurden aktualisiert
                    \Log::info("Keine Attribute für Gebäudetyp {$buildingType->value} aktualisiert");
                }
                
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
    
    /**
     * Aktualisiert die Benutzerattribute basierend auf den Gebäudeeffekten
     * 
     * @param int $userId Die ID des Benutzers
     * @param Building $building Das aktualisierte Gebäude
     * @param BuildingType $buildingType Der Gebäudetyp
     * @return array Liste der aktualisierten Attribute
     */
    private function updateUserAttributesForBuilding(int $userId, Building $building, BuildingType $buildingType): array
    {
        $effectAttributeNames = $buildingType->getEffectAttributes();
        $effectConfig = $buildingType->getEffectConfiguration();
        $effectType = $effectConfig['type'] ?? BuildingEffectType::MULTIPLICATIVE;
        $updatedAttributes = [];
        
        // Wenn keine Attribute definiert sind, frühzeitig beenden
        if (empty($effectAttributeNames)) {
            return $updatedAttributes;
        }
        
        foreach ($effectAttributeNames as $attributeName) {
            // Parameter basierend auf dem Effekttyp bestimmen
            $multiply = false;
            $replace = false;
            
            switch ($effectType) {
                case BuildingEffectType::ADDITIVE:
                    // Additive Effekte werden zum Wert hinzugefügt
                    $multiply = false;
                    $replace = false;
                    break;
                    
                case BuildingEffectType::MULTIPLICATIVE:
                    // Multiplikative Effekte verwenden den Wert als Faktor
                    $multiply = true;
                    $replace = false;
                    break;
                    
                case BuildingEffectType::EXPONENTIAL:
                case BuildingEffectType::LOGARITHMIC:
                    // Diese komplexen Typen ersetzen den Wert komplett
                    $multiply = false;
                    $replace = true;
                    break;
            }
            
            $updatedAttribute = $this->userAttributeService->updateUserAttribute(
                $userId,
                $attributeName,
                $building->effect_value,
                $multiply,
                $replace
            );
            
            if ($updatedAttribute) {
                $updatedAttributes[$attributeName] = [
                    'name' => $attributeName,
                    'new_value' => $updatedAttribute->attribute_value,
                    'effect_applied' => $building->effect_value,
                    'effect_type' => $effectType->value
                ];
            } else {
                \Log::warning("Attribut {$attributeName} konnte nicht aktualisiert werden", [
                    'user_id' => $userId,
                    'building_id' => $building->id,
                    'effect_value' => $building->effect_value
                ]);
            }
        }
        
        return $updatedAttributes;
    }
}
