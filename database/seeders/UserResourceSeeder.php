<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('user_resources')->truncate();

        $users = User::all();

        foreach ($users as $user) {
            // Beispielressourcen für jeden Benutzer hinzufügen
            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 99,
                'count' => 10_000_000
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 1,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 2,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 3,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 4,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 5,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 6,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 7,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 8,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 9,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 10,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 11,
                'count' => 500,
            ]);

            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => 12,
                'count' => 2000,
            ]);
        }
    }
}
