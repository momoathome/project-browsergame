<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DefaultUserSeeder::class);
        $this->call(BuildingDetailsSeeder::class);
        $this->call(SpacecraftDetailsSeeder::class);
        $this->call(SpacecraftSeeder::class);
        $this->call(BuildingSeeder::class);
        $this->call(ResourceSeeder::class);
        $this->call(MarketSeeder::class);

    }
}
