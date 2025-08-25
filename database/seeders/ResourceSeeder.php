<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Resource\Models\Resource;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resourcesConfig = config('game.resources.resources');

        foreach ($resourcesConfig as $resourceConfig) {
            Resource::create($resourceConfig);
        }
    }
}
