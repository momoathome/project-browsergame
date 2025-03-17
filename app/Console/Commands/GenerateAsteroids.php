<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GenerateAsteroidBatch;

class GenerateAsteroids extends Command
{
    /**
     * Der Befehlsname und Signatur der Konsolenanwendung.
     *
     * @var string
     */
    protected $signature = 'game:generate-asteroids 
                            {--count=2500 : Anzahl der zu generierenden Asteroiden}
                            {--batch=250 : Anzahl der Asteroiden pro Batch}';

    /**
     * Die Konsolenbefehls-Beschreibung.
     *
     * @var string
     */
    protected $description = 'Generiert Asteroiden für das Spiel';

    /**
     * Führt den Konsolenbefehl aus.
     *
     * @return int
     */
    public function handle()
    {
        $totalAsteroids = $this->option('count');
        $batchSize = $this->option('batch');
        $batches = ceil($totalAsteroids / $batchSize);

        $this->info("Starte Generierung von {$totalAsteroids} Asteroiden in {$batches} Batches");

        $progressBar = $this->output->createProgressBar($batches);
        $progressBar->start();

        for ($i = 0; $i < $batches; $i++) {
            $currentBatchSize = min($batchSize, $totalAsteroids - ($i * $batchSize));
            
            GenerateAsteroidBatch::dispatch($currentBatchSize)
                ->onQueue('asteroid-generation')
                ->delay(now()->addSeconds($i * 2));
                
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Asteroiden-Generierungsjobs wurden zur Queue hinzugefügt.');
        $this->info('Führen Sie "sail artisan queue:work --queue=asteroid-generation" aus, um die Jobs zu verarbeiten.');

        return 0;
    }
}
