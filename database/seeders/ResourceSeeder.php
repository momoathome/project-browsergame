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
            'image' => '/storage/resources/carbon.png'
        ]);

        Resource::create([
            'name' => 'Hydrogenium',
            'description' => 'A highly reactive gas',
            'image' => '/storage/resources/hydrogenium.png'
        ]);

        Resource::create([
            'name' => 'Kyberkristall',
            'description' => 'A rare and energy-rich crystal',
            'image' => '/storage/resources/kyberkristall.png'
        ]);

        Resource::create([
            'name' => 'Titanium',
            'description' => 'A strong and durable metal',
            'image' => '/storage/resources/titanium.png'
        ]);

        Resource::create([
            'name' => 'Uraninite',
            'description' => 'A major ore of uranium, used for nuclear fuel',
            'image' => '/storage/resources/uraninite.png'
        ]);

        Resource::create([
            'name' => 'Cobalt',
            'description' => 'A metal used in high-strength alloys',
            'image' => '/storage/resources/cobalt.png'
        ]);

        Resource::create([
            'name' => 'Iridium',
            'description' => 'A dense and corrosion-resistant metal',
            'image' => '/storage/resources/iridium.png'
        ]);

        Resource::create([
            'name' => 'Thorium',
            'description' => 'A radioactive element',
            'image' => '/storage/resources/thorium.png'
        ]);

        Resource::create([
            'name' => 'Hyperdiamond',
            'description' => 'A synthetic diamond with exceptional hardness',
            'image' => '/storage/resources/hyperdiamond.png'
        ]);

        Resource::create([
            'name' => 'Astatine',
            'description' => 'A very rare and highly radioactive element',
            'image' => '/storage/resources/astatine.png'
        ]);

        Resource::create([
            'name' => 'Dilithium',
            'description' => 'A crystalline element with unique properties',
            'image' => '/storage/resources/dilithium.png'
        ]);

        Resource::create([
            'name' => 'Deuterium',
            'description' => 'A stable isotope of hydrogen',
            'image' => '/storage/resources/deuterium.png'
        ]);
    }
}
