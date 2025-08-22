<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orion\Modules\Spacecraft\Models\SpacecraftDetails;


class SpacecraftDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SpacecraftDetails::create([
            'name' => 'Merlin',
            'description' => 'Agile light fighter for quick attacks.',
            'image' => '/images/spacecrafts/merlin.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Comet',
            'description' => 'Optimized for lightning-fast strikes.',
            'image' => '/images/spacecrafts/comet.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Javelin',
            'description' => 'Advanced fighter for tough missions.',
            'image' => '/images/spacecrafts/javelin.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Sentinel',
            'description' => 'Engineering masterpiece for any challenge.',
            'image' => '/images/spacecrafts/sentinel.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Probe',
            'description' => 'Essential for complex missions.',
            'image' => '/images/spacecrafts/probe.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Ares',
            'description' => 'Elite unit for demanding operations.',
            'image' => '/images/spacecrafts/ares.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nova',
            'description' => 'Superior unit, symbol of strength.',
            'image' => '/images/spacecrafts/nova.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Horus',
            'description' => 'Outperforms all previous models.',
            'image' => '/images/spacecrafts/horus.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reaper',
            'description' => 'Ultimate evolution of space fighters.',
            'image' => '/images/spacecrafts/reaper.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Mole',
            'description' => 'Agile mining ship for small tasks.',
            'image' => '/images/spacecrafts/mole.webp',
            'type' => 'Miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Titan',
            'description' => 'Massive miner for large operations.',
            'image' => '/images/spacecrafts/titan.webp',
            'type' => 'Miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nomad',
            'description' => 'Agile transporter for medium cargo.',
            'image' => '/images/spacecrafts/nomad.webp',
            'type' => 'Transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Hercules',
            'description' => 'Heavy transporter for large freight.',
            'image' => '/images/spacecrafts/hercules.webp',
            'type' => 'Transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Collector',
            'description' => 'Efficient ship for salvaging.',
            'image' => '/images/spacecrafts/collector.webp',
            'type' => 'Salvager',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reclaimer',
            'description' => 'Advanced ship for large-scale salvage.',
            'image' => '/images/spacecrafts/reclaimer.webp',
            'type' => 'Salvager',
        ]);
    }
}
