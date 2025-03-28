<?php

namespace App\Console\Commands;

use App\Models\User;
use Orion\Modules\Station\Services\SetupInitialStation;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GenerateTestStations extends Command
{
    /**
     * Der Befehlsname und Signatur der Konsolenanwendung.
     *
     * @var string
     */
    protected $signature = 'game:generate-test-stations 
                            {--count=25 : Anzahl der zu generierenden Teststationen}
                            {--truncate : Löscht vorhandene Stationen vor der Generierung}
                            {--use-regions : Platziert Stationen gezielt in reservierten Regionen}
                            {--refresh-regions : Generiert die Regionen neu vor der Stationserstellung}';

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
    public function handle(AsteroidGenerator $asteroidGenerator)
    {
        $count = $this->option('count');
        $truncate = $this->option('truncate');
        $useRegions = $this->option('use-regions');
        $refreshRegions = $this->option('refresh-regions');

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

        // Optional Regionen neu generieren
        if ($refreshRegions) {
            $this->info('Generiere neue reservierte Regionen...');
            Cache::forget('asteroid-generator:reserved-station-regions');
            $regions = $asteroidGenerator->reserveStationRegions($count);
            $this->info("Erfolgreich {$count} Regionen reserviert.");
        }

        // Prüfen, ob genug Regionen vorhanden sind, wenn wir diese nutzen wollen
        if ($useRegions) {
            $reservedRegions = Cache::get('asteroid-generator:reserved-station-regions', []);

            if (empty($reservedRegions)) {
                $this->info('Keine reservierten Regionen gefunden, generiere neue...');
                $reservedRegions = $asteroidGenerator->reserveStationRegions($count);
            }

            $regionCount = count($reservedRegions);
            if ($regionCount < $count) {
                $this->warn("Nur {$regionCount} Regionen verfügbar, aber {$count} angefordert. Generiere {$count} Regionen...");
                $reservedRegions = $asteroidGenerator->reserveStationRegions($count);
                $regionCount = count($reservedRegions);
                $this->info("Jetzt sind {$regionCount} Regionen verfügbar.");
            }

            $this->info("Verwende reservierte Regionen für die Stationsgenerierung ({$regionCount} verfügbar).");
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

        // Reservierte Regionen verwalten
        $availableRegions = [];
        if ($useRegions) {
            // Regionen klonen, damit wir sie markieren können
            $availableRegions = Cache::get('asteroid-generator:reserved-station-regions', []);
            // Zufällige Reihenfolge für bessere Verteilung
            shuffle($availableRegions);
        }

        // Progressbar erstellen
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $success = 0;
        $failures = 0;
        $coordinates = [];

        // Für jeden User eine Station erstellen
        foreach ($users as $index => $user) {
            if ($index >= $count) {
                break;
            }

            try {
                // Maximal 3 Versuche pro Station
                $maxTries = 3;
                $created = false;

                for ($try = 1; $try <= $maxTries; $try++) {
                    try {
                        $station = null;

                        if ($useRegions) {
                            // Eine Region aus der verfügbaren Liste verwenden
                            if (empty($availableRegions)) {
                                throw new \Exception("Keine Regionen mehr verfügbar, bitte mehr Regionen anlegen.");
                            }

                            // Erste verfügbare Region nehmen und aus der Liste entfernen
                            $region = array_shift($availableRegions);

                            // Zufällige Position in der Region generieren
                            $angle = rand(0, 360) * M_PI / 180;
                            $radius = $region['inner_radius'] ?? $region['radius'] ?? 0;
                            $distance = sqrt(rand(0, 100) / 100) * $radius;

                            $x = $region['station_x'] ?? $region['x'] ?? 0;
                            $y = $region['station_y'] ?? $region['y'] ?? 0;

                            $x += $distance * cos($angle);
                            $y += $distance * sin($angle);

                            // Station erstellen
                            $station = \Orion\Modules\Station\Models\Station::create([
                                'user_id' => $user->id,
                                'name' => $user->name,
                                'x' => (int) $x,
                                'y' => (int) $y,
                            ]);

                            // Cache aktualisieren - wichtig!
                            Cache::forget('setup-station:stations');
                        } else {
                            // Standard-Methode verwenden
                            $station = $stationService->create($user->id, $user->name);
                        }

                        $coordinates[] = ['x' => $station->x, 'y' => $station->y];
                        $success++;
                        $created = true;
                        break;
                    } catch (\Exception $e) {
                        Log::warning("Versuch {$try} für User {$user->name} fehlgeschlagen: " . $e->getMessage());
                        // Beim letzten Versuch die Exception weiterwerfen
                        if ($try == $maxTries) {
                            throw $e;
                        }
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

        // Rest des Codes bleibt unverändert...

        // Optional: Koordinaten visualisieren und Stationsdistanzen überprüfen
        if ($this->confirm('Möchten Sie eine einfache Visualisierung der Stationen sehen?', false)) {
            $this->visualizeCoordinates($coordinates);
        }

        // Prüfe minimale Distanz zwischen den erstellten Stationen
        if (count($coordinates) > 1 && $this->confirm('Möchten Sie die Mindestabstände zwischen den Stationen überprüfen?', true)) {
            $this->checkStationDistances($coordinates);
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

    /**
     * Überprüft die Mindestabstände zwischen den erstellten Stationen
     * 
     * @param array $coordinates
     * @return void
     */
    private function checkStationDistances(array $coordinates)
    {
        $config = config('game.stations');
        $minRequiredDistance = $config['min_distance'];

        $this->info("Überprüfe Mindestabstände (soll mindestens {$minRequiredDistance} sein)...");

        $tooCloseCount = 0;
        $minFoundDistance = PHP_INT_MAX;
        $tooCloseStations = [];

        // Alle Stationspaare überprüfen
        for ($i = 0; $i < count($coordinates); $i++) {
            for ($j = $i + 1; $j < count($coordinates); $j++) {
                $distance = sqrt(
                    pow($coordinates[$i]['x'] - $coordinates[$j]['x'], 2) +
                    pow($coordinates[$i]['y'] - $coordinates[$j]['y'], 2)
                );

                $minFoundDistance = min($minFoundDistance, $distance);

                if ($distance < $minRequiredDistance) {
                    $tooCloseCount++;
                    $tooCloseStations[] = [
                        'station1' => ['x' => $coordinates[$i]['x'], 'y' => $coordinates[$i]['y']],
                        'station2' => ['x' => $coordinates[$j]['x'], 'y' => $coordinates[$j]['y']],
                        'distance' => $distance
                    ];
                }
            }
        }

        if ($tooCloseCount > 0) {
            $this->error("Es wurden {$tooCloseCount} Stationspaare gefunden, die zu nah beieinander liegen!");
            $this->info("Die kleinste gefundene Distanz beträgt: " . number_format($minFoundDistance, 2));

            // Zeige die ersten 5 zu nahen Stationen an
            $this->info("Beispiele für zu nahe Stationen:");
            foreach (array_slice($tooCloseStations, 0, 5) as $index => $pair) {
                $this->error("Paar " . ($index + 1) . ": Station bei x=" . $pair['station1']['x'] . ", y=" . $pair['station1']['y'] .
                    " und Station bei x=" . $pair['station2']['x'] . ", y=" . $pair['station2']['y'] .
                    " - Distanz: " . number_format($pair['distance'], 2));
            }
        } else {
            $this->info("Alle Stationen haben den Mindestabstand eingehalten!");
            $this->info("Die kleinste gefundene Distanz beträgt: " . number_format($minFoundDistance, 2));
        }
    }
}
