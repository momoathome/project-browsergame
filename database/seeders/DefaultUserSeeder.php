<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
    }
}
