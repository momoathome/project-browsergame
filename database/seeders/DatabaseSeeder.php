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
        $this->call(ResourceSeeder::class);
        $this->call(MarketSeeder::class);
        $this->call(UserAttributeSeeder::class);
        $this->call(UserResourceSeeder::class);
        $this->call(BuildingSeeder::class);
        $this->call(SpacecraftSeeder::class);
        $this->call(AsteroidSeeder::class);
        $this->call(StationSeeder::class);

    }
}
