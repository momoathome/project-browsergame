<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Market;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Market::create([
            'resource_id' => 1, // Carbon
            'cost' => 150,
            'stock' => 655140,
        ]);
        
        Market::create([
            'resource_id' => 2, // Hydrogenium
            'cost' => 550,
            'stock' => 373035,
        ]);
        
        Market::create([
            'resource_id' => 3, // Kyberkristall
            'cost' => 1750,
            'stock' => 209399,
        ]);
        
        Market::create([
            'resource_id' => 4, // Titanium
            'cost' => 250,
            'stock' => 446644,
        ]);
        
        Market::create([
            'resource_id' => 5, // Uraninite
            'cost' => 2000,
            'stock' => 145600,
        ]);
        
        Market::create([
            'resource_id' => 6, // Cobalt
            'cost' => 1200,
            'stock' => 589700,
        ]);
        
        Market::create([
            'resource_id' => 7, // Iridium
            'cost' => 3000,
            'stock' => 80000,
        ]);
        
        Market::create([
            'resource_id' => 8, // Thorium
            'cost' => 5000,
            'stock' => 25000,
        ]);
        
        Market::create([
            'resource_id' => 9, // Hyperdiamond
            'cost' => 7500,
            'stock' => 9500,
        ]);
        
        Market::create([
            'resource_id' => 10, // Astatine
            'cost' => 10000,
            'stock' => 1000,
        ]);
        
        Market::create([
            'resource_id' => 11, // Dilithium
            'cost' => 15000,
            'stock' => 500,
        ]);
        
        Market::create([
            'resource_id' => 12, // Deuterium
            'cost' => 2000,
            'stock' => 123456,
        ]);
    }
}
