<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;


class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table(table: 'resources')->truncate();

        Resource::create([
            'name' => 'Carbon',
            'description' => 'A common resource',
            'image' => '/storage/resources/Carbon.png'
        ]);

        Resource::create([
            'name' => 'Hydrogenium',
            'description' => 'A highly reactive gas',
            'image' => '/storage/resources/Hydrogenium.png'
        ]);

        Resource::create([
            'name' => 'Kyberkristall',
            'description' => 'A rare and energy-rich crystal',
            'image' => '/storage/resources/Kyberkristall.png'
        ]);

        Resource::create([
            'name' => 'Titanium',
            'description' => 'A strong and durable metal',
            'image' => '/storage/resources/Titanium.png'
        ]);

        Resource::create([
            'name' => 'Uraninite',
            'description' => 'A major ore of uranium, used for nuclear fuel',
            'image' => '/storage/resources/Uraninite.png'
        ]);

        Resource::create([
            'name' => 'Cobalt',
            'description' => 'A metal used in high-strength alloys',
            'image' => '/storage/resources/Cobalt.png'
        ]);

        Resource::create([
            'name' => 'Iridium',
            'description' => 'A dense and corrosion-resistant metal',
            'image' => '/storage/resources/Iridium.png'
        ]);

        Resource::create([
            'name' => 'Thorium',
            'description' => 'A radioactive element',
            'image' => '/storage/resources/Thorium.png'
        ]);

        Resource::create([
            'name' => 'Hyperdiamond',
            'description' => 'A synthetic diamond with exceptional hardness',
            'image' => '/storage/resources/Hyperdiamond.png'
        ]);

        Resource::create([
            'name' => 'Astatine',
            'description' => 'A very rare and highly radioactive element',
            'image' => '/storage/resources/Astatine.png'
        ]);

        Resource::create([
            'name' => 'Dilithium',
            'description' => 'A crystalline element with unique properties',
            'image' => '/storage/resources/Dilithium.png'
        ]);

        Resource::create([
            'name' => 'Deuterium',
            'description' => 'A stable isotope of hydrogen',
            'image' => '/storage/resources/Deuterium.png'
        ]);

        Resource::create([
            'id' => 99,
            'name' => 'Credits',
            'description' => 'currency',
            'image' => '/storage/resources/Credits.png'
        ]);
    }
}
