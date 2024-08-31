<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\SetupInitialUserResources;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        DB::table('user_resources')->truncate();

        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $this->userResourceService->create($userId);
        }
    }
}
