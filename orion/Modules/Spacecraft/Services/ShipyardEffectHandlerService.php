<?php

namespace Orion\Modules\Spacecraft\Services;

use Illuminate\Support\Facades\Log;
use Orion\Modules\Building\Services\BuildingEffectService;
use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;



class ShipyardEffectHandlerService
{
    public function __construct(
        private readonly SpacecraftRepository $spacecraftRepository,
        private readonly SpacecraftProductionService $spacecraftProductionService,
        private readonly BuildingEffectService $buildingEffectService
    ) {
    }

    public function handleShipyardUpgrade(int $userId, $upgradedBuilding): void
    {
        $effects = $this->buildingEffectService->getEffects('Shipyard', $upgradedBuilding->level);
        $spacecrafts = $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetails($userId);

        Log::info('Handling Shipyard upgrade "' . $upgradedBuilding->level . '"', [
            'effects' => $effects,
            'spacecrafts' => $spacecrafts
        ]);

        $unlocks = $effects['unlock'] ?? [];
        foreach ($unlocks as $spacecraftName) {
            $spacecraft = $spacecrafts->first(function ($sc) use ($spacecraftName) {
                return $sc->details && $sc->details->name === $spacecraftName;
            });
            if ($spacecraft && !$spacecraft->unlocked) {
                $this->spacecraftProductionService->unlockSpacecraft($userId, $spacecraft);
            }
        }
    }
}
