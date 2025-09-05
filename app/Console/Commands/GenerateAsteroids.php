<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Asteroid\Models\Asteroid;
use Orion\Modules\Asteroid\Models\AsteroidResource;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;

class GenerateAsteroids extends Command
{
    protected $signature = 'game:generate-asteroids 
                            {--count=2500 : Anzahl der zu generierenden Asteroiden}
                            {--batch=250 : Anzahl der Asteroiden pro Batch}
                            {--clear : Vorher alle Asteroiden und Ressourcen löschen}';

    protected $description = 'Generiert Asteroiden für das Spiel';

    public function handle()
    {
        if ($this->option('clear')) {
            $this->info('Lösche alle vorhandenen Asteroiden und Ressourcen...');
            Log::info('Alle vorhandenen Asteroiden und Ressourcen werden gelöscht.');
            AsteroidResource::truncate();
            Asteroid::truncate();
            $this->info('Alle Asteroiden und Ressourcen wurden gelöscht.');
        }

        $totalAsteroids = (int)$this->option('count');
        $batchSize = (int)$this->option('batch');
        $batches = ceil($totalAsteroids / $batchSize);

        $this->info("Starte Generierung von {$totalAsteroids} Asteroiden in {$batches} Batches");
        Log::info("Asteroiden-Generierung gestartet: {$totalAsteroids} Asteroiden, Batchgröße {$batchSize}");

        $progressBar = $this->output->createProgressBar($batches);
        $progressBar->start();

        $asteroidGenerator = app(AsteroidGenerator::class);

        $startTime = microtime(true);

        for ($i = 0; $i < $batches; $i++) {
            $currentBatchSize = min($batchSize, $totalAsteroids - ($i * $batchSize));
            $batchStart = microtime(true);

            $asteroidGenerator->generateAsteroids($currentBatchSize);

            $batchDuration = round(microtime(true) - $batchStart, 2);
            Log::info("Batch {$i}/{$batches} generiert ({$currentBatchSize} Asteroiden) in {$batchDuration}s");
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $totalDuration = round(microtime(true) - $startTime, 2);
        $this->info("Asteroiden wurden direkt generiert. Dauer: {$totalDuration}s");
        Log::info("Asteroiden-Generierung abgeschlossen. Gesamtdauer: {$totalDuration}s");

        return 0;
    }
}
