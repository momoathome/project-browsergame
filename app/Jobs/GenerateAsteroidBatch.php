<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AsteroidGenerator;
use Illuminate\Support\Facades\Log;

class GenerateAsteroidBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $count;
    protected $options;

    /**
     * Erstellt eine neue Job-Instanz.
     *
     * @param int $count Anzahl der zu generierenden Asteroiden
     * @param array $options Zusätzliche Optionen
     * @return void
     */
    public function __construct(int $count, array $options = [])
    {
        $this->count = $count;
        $this->options = $options;
    }

    /**
     * Führt den Job aus.
     *
     * @param AsteroidGenerator $generator
     * @return void
     */
    public function handle(AsteroidGenerator $generator)
    {
        try {
            Log::info("Starte Generierung von {$this->count} Asteroiden");
            $asteroids = $generator->generateAsteroids($this->count);
            Log::info("Erfolgreich {$this->count} Asteroiden generiert");
        } catch (\Exception $e) {
            Log::error("Fehler bei Asteroiden-Generierung: " . $e->getMessage());
            // Exception erneut werfen, damit der Job als fehlgeschlagen markiert wird
            throw $e;
        }
    }

    /**
     * Wird aufgerufen, wenn der Job fehlschlägt.
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::error("Job zur Asteroiden-Generierung ist fehlgeschlagen: " . $exception->getMessage());
    }
}
