<?php

namespace App\Console\Commands;

use App\Services\SetupInitialStation;
use Illuminate\Console\Command;

class TestStationSpawns extends Command
{
    protected $signature = 'test:station-spawns 
                           {count=100 : Anzahl der zu findenden potenziellen Standorte} 
                           {--refresh : Vorhandene Daten überschreiben}
                           {--debug : Ausführliche Debug-Informationen anzeigen}';

    protected $description = 'Testet das Finden von potenziellen Stationsstandorten';

    public function handle(SetupInitialStation $setupStation)
    {
        $count = $this->argument('count');
        $forceRefresh = $this->option('refresh');
        $debug = $this->option('debug');

        $this->info("Suche {$count} potenzielle Stationsstandorte...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $lastValidationResults = [];

        $callback = function ($current, $data = null) use ($progressBar, &$lastValidationResults, $debug) {
            // Fortschrittsbalken aktualisieren
            $progressBar->setProgress($current);

            // Debug-Informationen speichern und ggf. anzeigen
            if ($data) {
                $lastValidationResults = $data;

                if ($debug) {
                    $this->newLine();
                    if (isset($data['message'])) {
                        $this->info($data['message']);
                    }
                    if (isset($data['attempts'])) {
                        $this->line("Versuche: {$data['attempts']}, Gefunden: {$current}");
                    }
                    if (isset($data['validationResults'])) {
                        $results = $data['validationResults'];
                        $this->line("Position: " . json_encode($data['currentPosition'] ?? 'unbekannt'));
                        $this->line("Validierung: Stationen " . ($results['station'] ? '✓' : '✗') .
                            ", Asteroiden " . ($results['asteroid'] ? '✓' : '✗') .
                            ", Ressourcen " . ($results['resource'] ? '✓' : '✗'));
                    }
                }
            }
        };

        $startTime = microtime(true);

        $spawns = $setupStation->checkPotentialStationSpawns(
            $count,
            $forceRefresh,
            $callback
        );

        $progressBar->finish();
        $this->newLine(2);

        $duration = microtime(true) - $startTime;
        $this->info("Gefunden: " . count($spawns) . " potenzielle Standorte in {$duration} Sekunden");

        if (count($spawns) === 0) {
            // Konfigurationsdetails anzeigen bei 0 Treffern
            $this->warn("Keine Standorte gefunden. Konfigurationsparameter:");
            $stationConfig = $setupStation->getConfigForDebugging();
            $this->table(
                ['Parameter', 'Wert'],
                collect($stationConfig)->map(function ($value, $key) {
                    return [$key, is_array($value) ? json_encode($value) : $value];
                })->toArray()
            );
        }

        return count($spawns) > 0 ? 0 : 1;
    }
}
