<?php

namespace Database\Seeders;

use App\Services\SetupInitialUserAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserAttributeSeeder extends Seeder
{

    protected $userAttributeService;

    public function __construct(SetupInitialUserAttribute $userAttributeService)
    {
        $this->userAttributeService = $userAttributeService;
    }

    public function run(): void
    {
        DB::table('user_attributes')->truncate();

        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $this->userAttributeService->create($userId);
        }
    }
}
