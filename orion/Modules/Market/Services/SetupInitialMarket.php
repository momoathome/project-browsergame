<?php

namespace Orion\Modules\Market\Services;

use Orion\Modules\Market\Models\Market;
use Orion\Modules\Resource\Models\Resource;

class SetupInitialMarket
{
    public function create()
    {
        $resources = Resource::pluck('id', 'name')->toArray();
        $marketsConfig = config('game.market.markets');

        foreach ($marketsConfig as $marketConfig) {
            Market::create([
                'resource_id' => $resources[$marketConfig['resource_name']],
                'category' => $marketConfig['category'],
                'stock' => $marketConfig['stock'],
            ]);
        }
    }
}
