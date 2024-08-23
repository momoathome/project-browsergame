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
            'image' => 'Merlin.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Comet',
            'description' => 'Dieser Jäger wurde optimiert, um blitzschnelle Angriffe auszuführen.',
            'image' => 'Comet.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Javelin',
            'description' => 'Ein fortschrittlicher Jäger für anspruchsvolle Missionen.',
            'image' => 'Javelin.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Sentinel',
            'description' => 'Eine Meisterleistung der Ingenieurskunst. Ein Jäger für jede Herausforderung.',
            'image' => 'Sentinel.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Probe',
            'description' => 'Dieser hochentwickelte Jäger, ist unverzichtbar für komplexe Missionen.',
            'image' => 'Probe.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Ares',
            'description' => 'Quantensprung der Technologie: Eine Eliteeinheit für anspruchsvolle Operationen.',
            'image' => 'Ares.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nova',
            'description' => 'Ein Wunderwerk der Technologie. Eine überlegene Einheit und Symbol für Stärke.',
            'image' => 'Nova.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Horus',
            'description' => 'Übertrifft alle bisherigen Modelle, bereit für die härtesten Schlachten.',
            'image' => 'Horus.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reaper',
            'description' => 'Die ultimative Evolution der Raumjäger. Ein Symbol für technologische Überlegenheit.',
            'image' => 'Reaper.png',
            'type' => 'fighter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Mole',
            'description' => 'Ein kompaktes, wendiges Bergbauschiff. Perfekt für kleinere Abbauaufgaben.',
            'image' => 'Mole.png',
            'type' => 'miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Titan',
            'description' => 'Ein massives Bergbauschiff, die beste Wahl für große Abbauaufgaben.',
            'image' => 'Titan.png',
            'type' => 'miner',
        ]);

        SpacecraftDetails::create([
            'name' => 'Nomad',
            'description' => 'Ein agiler Transporter, der mittlere Frachten mit Leichtigkeit bewegt.',
            'image' => 'Nomad.png',
            'type' => 'transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Hercules',
            'description' => 'Ein massiver Transporter, der zum Transport für große Frachten entwickelt wurde.',
            'image' => 'Hercules.png',
            'type' => 'transporter',
        ]);

        SpacecraftDetails::create([
            'name' => 'Collector',
            'description' => 'description',
            'image' => 'Collector.png',
            'type' => 'salvager',
        ]);

        SpacecraftDetails::create([
            'name' => 'Reclaimer',
            'description' => 'description',
            'image' => 'Reclaimer.png',
            'type' => 'salvager',
        ]);
    }
}
