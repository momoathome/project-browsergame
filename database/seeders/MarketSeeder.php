<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Market\Models\Market;
use Orion\Modules\Resource\Models\Resource;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
