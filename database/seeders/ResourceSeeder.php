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
            'name' => 'Carbon',
            'description' => 'A common resource',
            'image' => 'carbon.png'
        ]);

        Resource::create([
            'name' => 'Hydrogenium',
            'description' => 'A highly reactive gas',
            'image' => 'hydrogenium.png'
        ]);

        Resource::create([
            'name' => 'Kyberkristall',
            'description' => 'A rare and energy-rich crystal',
            'image' => 'kyberkristall.png'
        ]);

        Resource::create([
            'name' => 'Titanium',
            'description' => 'A strong and durable metal',
            'image' => 'titanium.png'
        ]);

        Resource::create([
            'name' => 'Uraninite',
            'description' => 'A major ore of uranium, used for nuclear fuel',
            'image' => 'uraninite.png'
        ]);

        Resource::create([
            'name' => 'Cobalt',
            'description' => 'A metal used in high-strength alloys',
            'image' => 'Cobalt.png'
        ]);

        Resource::create([
            'name' => 'Iridium',
            'description' => ' A dense and corrosion-resistant metal',
            'image' => 'Iridium.png'
        ]);

        Resource::create([
            'name' => 'Thorium',
            'description' => 'A radioactive element',
            'image' => 'Thorium.png'
        ]);

        Resource::create([
            'name' => 'Hyperdiamond',
            'description' => 'A synthetic diamond with exceptional hardness',
            'image' => 'Hyperdiamond.png'
        ]);

        Resource::create([
            'name' => 'Astatine',
            'description' => 'A very rare and highly radioactive element',
            'image' => 'Astatine.png'
        ]);

        Resource::create([
            'name' => 'Dilithium',
            'description' => 'A crystalline element with unique properties',
            'image' => 'Dilithium.png'
        ]);

        Resource::create([
            'name' => 'Deuterium',
            'description' => 'A stable isotope of hydrogen',
            'image' => 'Deuterium.png'
        ]);
    }
}
