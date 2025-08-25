<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Building\Services\SetupInitialBuildings;
use Illuminate\Support\Facades\DB;

class BuildingSeeder extends Seeder
{
    protected $buildingService;

    public function __construct(SetupInitialBuildings $buildingService)
    {
        $this->buildingService = $buildingService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $this->buildingService->create($userId);
        }
    }
}
