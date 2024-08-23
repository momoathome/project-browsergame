<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resource;


class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Resource::create([
            'name' => 'carbon',
            'description' => 'A common resource',
        ]);

        Resource::create([
            'name' => 'hydrogenium',
            'description' => 'A highly reactive gas',
        ]);

        Resource::create([
            'name' => 'kyberkristall',
            'description' => 'A rare and energy-rich crystal',
        ]);

        Resource::create([
            'name' => 'titanium',
            'description' => 'A strong and durable metal',
        ]);

        Resource::create([
            'name' => 'Uraninite',
            'description' => 'A major ore of uranium, used for nuclear fuel',
        ]);

        Resource::create([
            'name' => 'Cobalt',
            'description' => 'A metal used in high-strength alloys',
        ]);

        Resource::create([
            'name' => 'Iridium',
            'description' => ' A dense and corrosion-resistant metal',
        ]);

        Resource::create([
            'name' => 'Thorium',
            'description' => 'A radioactive element',
        ]);

        Resource::create([
            'name' => 'Hyperdiamond',
            'description' => 'A synthetic diamond with exceptional hardness',
        ]);

        Resource::create([
            'name' => 'Astatine',
            'description' => 'A very rare and highly radioactive element',
        ]);

        Resource::create([
            'name' => 'Dilithium',
            'description' => 'A crystalline element with unique properties',
        ]);

        Resource::create([
            'name' => 'Deuterium',
            'description' => 'A stable isotope of hydrogen',
        ]);
    }
}
