<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Services\SetupInitialSpacecrafts;
use Illuminate\Support\Facades\DB;

class SpacecraftSeeder extends Seeder
{
    protected $spacecraftService;

    public function __construct(SetupInitialSpacecrafts $spacecraftService)
    {
        $this->spacecraftService = $spacecraftService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table(table: 'spacecrafts')->truncate();

        $userIds = [1, 2];

        foreach ($userIds as $userId) {
            $this->spacecraftService->create($userId);
        }
    }
}
