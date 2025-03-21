<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Orion\Modules\Station\Services\SetupInitialStation;

class StationSeeder extends Seeder
{
    protected $stationService;

    public function __construct(SetupInitialStation $stationService)
    {
        $this->stationService = $stationService;
    }

    public function run(): void
    {
        DB::table(table: 'stations')->truncate();

        $users = User::all();

        foreach ($users as $user) {
            $this->stationService->create($user->id, $user->name);
        }
    }
}
