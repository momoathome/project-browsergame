<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table(table: 'users')->truncate();

        User::create([
            'name' => 'admin',
            'email' => 'admin@browsergame.de',
            'password' => Hash::make('password')
        ]);

        // tester
        User::create([
            'name' => 'tester',
            'email' => 'tester@browsergame.de',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'user1',
            'email' => 'user1@browsergame.de',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'user2',
            'email' => 'user2@browsergame.de',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'user3',
            'email' => 'user3@browsergame.de',
            'password' => Hash::make('password')
        ]);
    }
}
