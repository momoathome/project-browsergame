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
            'description' => 'Ein agiler, leichter Raumjäger, der für rasche Angriffe konzipiert wurde.',
            'image' => '/images/spacecrafts/merlin.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Comet',
            'description' => 'Dieser Jäger wurde optimiert, um blitzschnelle Angriffe auszuführen.',
            'image' => '/images/spacecrafts/comet.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Javelin',
            'description' => 'Ein fortschrittlicher Jäger für anspruchsvolle Missionen.',
            'image' => '/images/spacecrafts/javelin.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Sentinel',
            'description' => 'Eine Meisterleistung der Ingenieurskunst. Ein Jäger für jede Herausforderung.',
            'image' => '/images/spacecrafts/sentinel.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Probe',
            'description' => 'Dieser hochentwickelte Jäger, ist unverzichtbar für komplexe Missionen.',
            'image' => '/images/spacecrafts/probe.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Ares',
            'description' => 'Quantensprung der Technologie: Eine Eliteeinheit für anspruchsvolle Operationen.',
            'image' => '/images/spacecrafts/ares.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nova',
            'description' => 'Ein Wunderwerk der Technologie. Eine überlegene Einheit und Symbol für Stärke.',
            'image' => '/images/spacecrafts/nova.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Horus',
            'description' => 'Übertrifft alle bisherigen Modelle, bereit für die härtesten Schlachten.',
            'image' => '/images/spacecrafts/horus.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reaper',
            'description' => 'Die ultimative Evolution der Raumjäger. Ein Symbol für technologische Überlegenheit.',
            'image' => '/images/spacecrafts/reaper.webp',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Mole',
            'description' => 'Ein kompaktes, wendiges Bergbauschiff. Perfekt für kleinere Abbauaufgaben.',
            'image' => '/images/spacecrafts/mole.webp',
            'type' => 'Miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Titan',
            'description' => 'Ein massives Bergbauschiff, die beste Wahl für große Abbauaufgaben.',
            'image' => '/images/spacecrafts/titan.webp',
            'type' => 'Miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nomad',
            'description' => 'Ein agiler Transporter, der mittlere Frachten mit Leichtigkeit bewegt.',
            'image' => '/images/spacecrafts/nomad.webp',
            'type' => 'Transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Hercules',
            'description' => 'Ein massiver Transporter, der zum Transport für große Frachten entwickelt wurde.',
            'image' => '/images/spacecrafts/hercules.webp',
            'type' => 'Transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Collector',
            'description' => 'description',
            'image' => '/images/spacecrafts/collector.webp',
            'type' => 'Salvager',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reclaimer',
            'description' => 'description',
            'image' => '/images/spacecrafts/reclaimer.webp',
            'type' => 'Salvager',
        ]);
    }
}
