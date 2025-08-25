<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Orion\Modules\User\Services\SetupInitialUserResources;

class UserResourceSeeder extends Seeder
{
    protected $userResourceService;

    public function __construct(SetupInitialUserResources $userResourceService)
    {
        $this->userResourceService = $userResourceService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $this->userResourceService->create($userId);
        }
    }
}
