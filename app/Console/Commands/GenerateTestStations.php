<?php

namespace App\Console\Commands;

use App\Models\User;
use Orion\Modules\Station\Services\SetupInitialStation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateTestStations extends Command
{
    /**
     * Der Befehlsname und Signatur der Konsolenanwendung.
     *
     * @var string
     */
    protected $signature = 'game:generate-test-stations 
                            {--count=50 : Anzahl der zu generierenden Teststationen}
                            {--truncate : Löscht vorhandene Stationen vor der Generierung}';

    /**
     * Die Konsolenbefehls-Beschreibung.
     *
     * @var string
     */
    protected $description = 'Generiert Teststationen, um die Station-Platzierung zu testen';

    /**
     * Führt den Konsolenbefehl aus.
     *
     * @return int
     */
    public function handle()
    {
        $count = $this->option('count');
        $truncate = $this->option('truncate');

        $this->info("Starte Generierung von {$count} Teststationen...");

        if ($truncate) {
            if ($this->confirm('Alle vorhandenen Stationen werden gelöscht. Fortfahren?', true)) {
                $this->info('Lösche vorhandene Stationen...');
                DB::table('stations')->truncate();
                // Cache zurücksetzen
                SetupInitialStation::clearCache();
                $this->info('Stationen gelöscht.');
            } else {
                $this->info('Operation abgebrochen.');
                return 1;
            }
        }

        // Startzeit für Performance-Messung
        $startTime = microtime(true);

        // Teststationen generieren
        $stationService = new SetupInitialStation();

        // Bestehende Test-User finden oder Test-User erstellen
        $users = User::where('name', 'like', 'testuser%')->get();
        $existingCount = $users->count();

        if ($existingCount < $count) {
            $this->info("Erstelle " . ($count - $existingCount) . " Test-User...");
            for ($i = $existingCount + 1; $i <= $count; $i++) {
                $user = User::create([
                    'name' => "testuser{$i}",
                    'email' => "testuser{$i}@example.com",
                    'password' => bcrypt('password'),
                ]);
                $users->push($user);
            }
        }

        // Progressbar erstellen
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $success = 0;
        $failures = 0;
        $coordinates = [];

        // Für jeden User eine Station erstellen
        foreach ($users as $index => $user) {
            if ($index >= $count)
                break;

            try {
                // Maximal 3 Versuche pro Station
                $maxTries = 3;
                $created = false;

                for ($try = 1; $try <= $maxTries; $try++) {
                    try {
                        $station = $stationService->create($user->id, $user->name);
                        $coordinates[] = ['x' => $station->x, 'y' => $station->y];
                        $success++;
                        $created = true;
                        break;
                    } catch (\Exception $e) {
                        Log::warning("Versuch {$try} für User {$user->name} fehlgeschlagen: " . $e->getMessage());
                        // Beim letzten Versuch die Exception weiterwerfen
                        if ($try == $maxTries)
                            throw $e;
                    }
                }

                if (!$created) {
                    $failures++;
                }
            } catch (\Exception $e) {
                $this->error("Fehler bei User {$user->name}: " . $e->getMessage());
                $failures++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Endzeit und Performance-Ausgabe
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->info("Generierung abgeschlossen!");
        $this->info("Erfolgreiche Stationen: {$success}");
        $this->info("Fehlgeschlagene Stationen: {$failures}");
        $this->info("Ausführungszeit: " . number_format($executionTime, 2) . " Sekunden");

        // Optional: Koordinaten visualisieren
        if ($this->confirm('Möchten Sie eine einfache Visualisierung der Stationen sehen?', false)) {
            $this->visualizeCoordinates($coordinates);
        }

        return 0;
    }

    /**
     * Visualisiert die generierten Stationskoordinaten.
     *
     * @param array $coordinates
     * @return void
     */
    private function visualizeCoordinates(array $coordinates)
    {
        $config = config('game.stations');
        $universeSize = $config['universe_size'];

        $this->info("Universum-Größe: {$universeSize} x {$universeSize}");
        $this->info("Mindestabstand zwischen Stationen: {$config['min_distance']}");
        $this->info("Mindestabstand zu Asteroiden: {$config['asteroid_min_distance']}");

        // Einfache Statistiken
        $minX = min(array_column($coordinates, 'x'));
        $maxX = max(array_column($coordinates, 'x'));
        $minY = min(array_column($coordinates, 'y'));
        $maxY = max(array_column($coordinates, 'y'));
        $spanX = $maxX - $minX;
        $spanY = $maxY - $minY;

        $this->info("Koordinatenbereich: X von {$minX} bis {$maxX}, Y von {$minY} bis {$maxY}");
        $this->info("Abdeckung: " . round(($spanX * $spanY) / ($universeSize * $universeSize) * 100, 2) . "% des Universums");

        // Distribution der Stationen (einfaches Histogramm)
        $this->info("Verteilungsvisualisierung:");
        $gridSize = 10; // 10x10 Grid für die Visualisierung
        $grid = array_fill(0, $gridSize, array_fill(0, $gridSize, 0));

        foreach ($coordinates as $coord) {
            $gridX = min($gridSize - 1, floor($coord['x'] / $universeSize * $gridSize));
            $gridY = min($gridSize - 1, floor($coord['y'] / $universeSize * $gridSize));
            $grid[$gridY][$gridX]++;
        }

        // Visualisierung ausgeben
        for ($y = 0; $y < $gridSize; $y++) {
            $line = '';
            for ($x = 0; $x < $gridSize; $x++) {
                $count = $grid[$y][$x];
                if ($count == 0)
                    $line .= '·';
                else if ($count < 3)
                    $line .= '○';
                else if ($count < 6)
                    $line .= '◎';
                else if ($count < 10)
                    $line .= '●';
                else
                    $line .= '★';
            }
            $this->line($line);
        }

        $this->info("Legende: · (0), ○ (1-2), ◎ (3-5), ● (6-9), ★ (10+)");
    }
}
