<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\AsteroidSeeder;
use Database\Seeders\StationSeeder;
use Database\Seeders\RebelSeeder;

class RegenerateUniverse extends Command
{
    protected $signature = 'game:regenerate-universe';
    protected $description = 'Generiert das Universum komplett neu (Asteroiden, Stationen, Rebellen)';

    public function handle()
    {
        $this->info('Universum wird neu generiert...');

        $this->info('Lösche alte Asteroiden...');
        \Orion\Modules\Asteroid\Models\Asteroid::truncate();
        \Orion\Modules\Asteroid\Models\AsteroidResource::truncate();
        
        $this->info('Lösche alte Stationen...');
        \Orion\Modules\Station\Models\Station::truncate();

        $this->info('Lösche alte Stationsregionen...');
        \Orion\Modules\Station\Models\StationRegion::truncate();

        $this->info('Lösche alte Rebellen...');
        \Orion\Modules\Rebel\Models\Rebel::truncate();

        $this->info('Erzeuge neue Asteroiden...');
        $this->call(AsteroidSeeder::class);

        $this->info('Erzeuge neue Stationen...');
        $this->call(StationSeeder::class);

        $this->info('Erzeuge neue Rebellen...');
        $this->call(RebelSeeder::class);

        $this->info('Universum erfolgreich neu generiert!');
    }
}
