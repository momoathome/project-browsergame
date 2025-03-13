<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('resources')->truncate();

        $resourcesConfig = config('game.resources.resources');

        foreach ($resourcesConfig as $resourceConfig) {
            Resource::create($resourceConfig);
        }
    }
}
