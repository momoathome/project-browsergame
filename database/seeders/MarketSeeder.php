<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Market;
use App\Models\Resource;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = Resource::pluck('id', 'name')->toArray();
        $marketsConfig = config('market.markets');

        foreach ($marketsConfig as $marketConfig) {
            Market::create([
                'resource_id' => $resources[$marketConfig['resource_name']],
                'cost' => $marketConfig['cost'],
                'stock' => $marketConfig['stock'],
            ]);
        }
    }
}
