<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpacecraftDetails;


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
            'image' => '/storage/spacecrafts/merlin.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Comet',
            'description' => 'Dieser Jäger wurde optimiert, um blitzschnelle Angriffe auszuführen.',
            'image' => '/storage/spacecrafts/comet.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Javelin',
            'description' => 'Ein fortschrittlicher Jäger für anspruchsvolle Missionen.',
            'image' => '/storage/spacecrafts/javelin.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Sentinel',
            'description' => 'Eine Meisterleistung der Ingenieurskunst. Ein Jäger für jede Herausforderung.',
            'image' => '/storage/spacecrafts/sentinel.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Probe',
            'description' => 'Dieser hochentwickelte Jäger, ist unverzichtbar für komplexe Missionen.',
            'image' => '/storage/spacecrafts/probe.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Ares',
            'description' => 'Quantensprung der Technologie: Eine Eliteeinheit für anspruchsvolle Operationen.',
            'image' => '/storage/spacecrafts/ares.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nova',
            'description' => 'Ein Wunderwerk der Technologie. Eine überlegene Einheit und Symbol für Stärke.',
            'image' => '/storage/spacecrafts/nova.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Horus',
            'description' => 'Übertrifft alle bisherigen Modelle, bereit für die härtesten Schlachten.',
            'image' => '/storage/spacecrafts/horus.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reaper',
            'description' => 'Die ultimative Evolution der Raumjäger. Ein Symbol für technologische Überlegenheit.',
            'image' => '/storage/spacecrafts/reaper.jpg',
            'type' => 'Fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Mole',
            'description' => 'Ein kompaktes, wendiges Bergbauschiff. Perfekt für kleinere Abbauaufgaben.',
            'image' => '/storage/spacecrafts/mole.jpg',
            'type' => 'Miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Titan',
            'description' => 'Ein massives Bergbauschiff, die beste Wahl für große Abbauaufgaben.',
            'image' => '/storage/spacecrafts/titan.jpg',
            'type' => 'Miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nomad',
            'description' => 'Ein agiler Transporter, der mittlere Frachten mit Leichtigkeit bewegt.',
            'image' => '/storage/spacecrafts/nomad.jpg',
            'type' => 'Transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Hercules',
            'description' => 'Ein massiver Transporter, der zum Transport für große Frachten entwickelt wurde.',
            'image' => '/storage/spacecrafts/hercules.jpg',
            'type' => 'Transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Collector',
            'description' => 'description',
            'image' => '/storage/spacecrafts/collector.jpg',
            'type' => 'Salvager',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reclaimer',
            'description' => 'description',
            'image' => '/storage/spacecrafts/reclaimer.jpg',
            'type' => 'Salvager',
        ]);
    }
}
