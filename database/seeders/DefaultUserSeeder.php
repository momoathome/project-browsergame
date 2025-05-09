<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleUser = Role::create(['name' => 'user']);

        DB::table(table: 'users')->truncate();

        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@browsergame.de',
            'password' => Hash::make('password')
        ]);

        $admin->assignRole($roleAdmin);

        // tester
        $user = User::create([
            'name' => 'tester',
            'email' => 'tester@browsergame.de',
            'password' => Hash::make('password')
        ]);

        $user->assignRole($roleUser);
    }
}
