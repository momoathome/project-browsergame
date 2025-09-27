<?php

namespace Orion\Modules\Market\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
use App\Events\UpdateUserResources;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Market\Models\Market;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Market\Repositories\MarketRepository;
use Orion\Modules\Market\Exceptions\InsufficientResourceException;

readonly class MarketService
{
    public readonly array $resourceCategoryMap;

    public function __construct(
        private readonly MarketRepository $marketRepository,
        private readonly AuthManager $authManager,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService
    ) {
        $resourcePools = config('game.asteroids.resource_pools');
        $map = [];
        foreach ($resourcePools as $category => $data) {
            foreach ($data['resources'] as $resName) {
                $map[$resName] = $category;
            }
        }
        $this->resourceCategoryMap = $map;
    }

    public function getMarketData()
    {
        $marketData = $this->marketRepository->getMarketData();

        // Füge die Kategorie zu jedem Eintrag hinzu
        foreach ($marketData as &$entry) {
            $entry['category'] = $this->getCategory($entry['resource']['name']);
        }

        return $marketData;
    }

    public function updateResourceAmount($resourceId, $stock)
    {
        $this->marketRepository->updateResourceAmount($resourceId, $stock);
    }

    protected function getCategory(string $resourceName): ?string
    {
        return $this->resourceCategoryMap[$resourceName] ?? null;
    }

    /**
     * Tauscht eine Ressource gegen eine andere auf Basis ihrer Kostenwerte
     */
    public function tradeResources($user, Market $giveRes, int $giveQty, Market $receiveRes): array
    {
        // ==========================================================
        // Resource Trade Calculation
        //
        // FORMEL:
        //  1. Berechne Punkte für abgegebene Ressource:
        //        givePoints = giveQty * categoryValues[giveCategory]
        //
        //  2. Berechne Punktewert der Zielressource:
        //        receiveQtyRaw = givePoints / categoryValues[receiveCategory]
        //
        //  3. Wende Marktgebühr an (z. B. 5%):
        //        fee = floor(receiveQtyRaw * 0.05)
        //        receiveQty = max(receiveQtyRaw - fee, 0)
        //
        //  4. Wenn receiveQty <= 0 → Trade nicht möglich
        //
        // BEISPIELE:
        //  - 100 Carbon (low_value=1) → Cobalt (medium_value=4)
        //        givePoints = 100 * 1 = 100
        //        receiveQtyRaw = 100 / 4 = 25
        //        receiveQty = 25 - 1 (5%) = 24
        //
        //  - 10 Cobalt (medium_value=4) → Carbon (low_value=1)
        //        givePoints = 10 * 4 = 40
        //        receiveQtyRaw = 40 / 1 = 40
        //        receiveQty = 38 nach Fee
        //
        //  - 250 Carbon (low_value=1) → Dilithium (extreme_value=25)
        //        givePoints = 250
        //        receiveQtyRaw = 250 / 25 = 10
        //        receiveQty = 9 nach Fee
        //
        // FAUSTREGELN FÜR BALANCING:
        //  - Je höher der categoryValue, desto teurer/selten ist die Ressource.
        //  - Werte können angepasst werden, um das Ökosystem auszugleichen:
        //        - 'low_value' hochsetzen => "billige" Ressourcen wertvoller
        //        - 'extreme_value' hochsetzen => extreme Ressourcen schwerer zu bekommen
        // ==========================================================

        // Kategorie-Basiswerte
        $categoryValues = config('game.market.market_category_values');

        if (!$categoryValues || count($categoryValues) === 0) {
            return [
                'success' => false,
                'message' => 'Market category values not configured.'
            ];
        }

        $giveCategory = $this->getCategory($giveRes->resource->name);
        $receiveCategory = $this->getCategory($receiveRes->resource->name);

        if (!$giveCategory || !$receiveCategory) {
            return [
                'success' => false,
                'message' => 'Kategorie not found for one of the resources.'
            ];
        }

        $giveValue = $giveQty * $categoryValues[$giveCategory];
        $receiveQty = (int) floor($giveValue / $categoryValues[$receiveCategory]);

        // 5% Marktgebühr
        $fee = (int) floor($receiveQty * 0.05);
        $finalQty = max($receiveQty - $fee, 0);

        if ($finalQty <= 0) {
            return [
                'success' => false,
                'message' => 'Trade not possible: Amount too low after fees.'
            ];
        }

        try {
            DB::transaction(function () use ($user, $giveRes, $giveQty, $receiveRes, $finalQty) {
                $userGiveRes = $this->userResourceService->getSpecificUserResource($user->id, $giveRes->resource_id);

                if (!$userGiveRes || $userGiveRes->amount < $giveQty) {
                    throw new InsufficientResourceException();
                }

                if ($receiveRes->stock < $finalQty) {
                    throw new InsufficientResourceException();
                }

                // Spieler gibt Ressource ab
                $this->userResourceService->subtractResourceAmount($user, $giveRes->resource_id, $giveQty);

                // Marktstock aktualisieren
                $this->marketRepository->increaseStock($giveRes->resource_id, $giveQty);
                $this->marketRepository->decreaseStock($receiveRes->resource_id, $finalQty);

                // Spieler erhält Zielressource
                $this->userResourceService->addResourceAmount($user, $receiveRes->resource_id, $finalQty);
            });

            broadcast(new UpdateUserResources($user));

            return [
                'success' => true,
                'message' => "Successfully exchanged {$giveQty} {$giveRes->resource->name} for {$finalQty} {$receiveRes->resource->name}."
            ];
        } catch (InsufficientResourceException $e) {
            return [
                'success' => false,
                'message' => 'Not enough resources for the player or market.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Trade failed: ' . $e->getMessage()
            ];
        }
    }

}

