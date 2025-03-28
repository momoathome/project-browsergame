<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Asteroid\Services\UniverseService;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;

class TestStationSpawns extends Command
{
    protected $signature = 'test:station-spawns 
                           {count=100 : Anzahl der zu findenden potenziellen Standorte} 
                           {--refresh : Vorhandene Daten überschreiben}
                           {--debug : Ausführliche Debug-Informationen anzeigen}
                           {--clear-cache : Gesamten Cache löschen vor der Suche}
                           {--reserve-regions=0 : Anzahl neuer Regionen reservieren (0 = keine)}
                           {--test-reserved : Testen, ob in allen reservierten Regionen Stationen platziert werden können}
                           {--show-all : Alle gefundenen Regionen anzeigen, nicht nur die ersten 5}';

    protected $description = 'Testet das Finden von potenziellen Stationsstandorten';

    public function handle(
        UniverseService $universeService,
        AsteroidGenerator $asteroidGenerator
    ) {
        $count = $this->argument('count');
        $forceRefresh = $this->option('refresh');
        $debug = $this->option('debug');
        $regionsToReserve = (int) $this->option('reserve-regions');
        $testReserved = $this->option('test-reserved');
        $showAll = $this->option('show-all');

        // Cache komplett leeren wenn gewünscht
        if ($this->option('clear-cache')) {
            $this->info("Lösche den gesamten Cache...");
            $universeService->clearCache();
            $forceRefresh = true;

            sleep(1);
        }

        // Optional neue Regionen reservieren
        if ($regionsToReserve > 0) {
            $this->info("Reserviere {$regionsToReserve} neue Regionen für Stationsstandorte...");
            $startTime = microtime(true);

            Cache::forget('universe:reserved-station-regions');

            $regions = $universeService->reserveStationRegions($regionsToReserve, true);
            $duration = microtime(true) - $startTime;
            $this->info("Erfolgreich " . count($regions) . " Regionen in " . number_format($duration, 4) . " Sekunden reserviert.");

            // Details anzeigen
            $displayCount = $showAll ? count($regions) : min(5, count($regions));

            $this->table(
                ['#', 'X', 'Y', 'Inner Radius', 'Outer Radius'],
                collect(array_slice($regions, 0, $displayCount))->map(function ($region, $key) {
                    return [
                        '#' => $key + 1,
                        'X' => $region['station_x'] ?? $region['x'] ?? 0,
                        'Y' => $region['station_y'] ?? $region['y'] ?? 0,
                        'Inner Radius' => $region['inner_radius'] ?? '-',
                        'Outer Radius' => $region['outer_radius'] ?? $region['radius'] ?? 0
                    ];
                })
            );

            if (!$showAll && count($regions) > 5) {
                $this->info("(Es werden nur die ersten 5 von " . count($regions) . " Regionen angezeigt)");
            }
        }

        // Teste alle reservierten Regionen, ob Stationen platziert werden können
        if ($testReserved) {
            $this->info("Teste, ob in reservierten Regionen Stationen platziert werden können...");
            $reservedRegions = $universeService->getReservedStationRegions();

            if (empty($reservedRegions)) {
                $this->warn("Keine reservierten Regionen gefunden!");
            } else {
                $this->info("Gefunden: " . count($reservedRegions) . " reservierte Regionen");

                $validPositions = 0;
                $invalidPositions = 0;
                $validRegions = [];
                $invalidRegions = []; // Array für ungültige Regionen

                // Teste jede Region auf Platzierbarkeit
                $progressBar = $this->output->createProgressBar(count($reservedRegions));
                $progressBar->start();

                foreach ($reservedRegions as $index => $region) {
                    $isValid = false;
                    $x = $region['station_x'] ?? $region['x'] ?? 0;
                    $y = $region['station_y'] ?? $region['y'] ?? 0;
                    $regionId = $region['id'] ?? $index;

                    try {
                        // Direkt das Zentrum als Station verwenden, keine zufällige Position
                        $minDistance = config('game.core.station_distance');
                        $asteroidMinDistance = config('game.core.asteroid_to_station_distance');

                        // Prüfe Kollisionen mit ANDEREN Stationen (nicht mit sich selbst)
                        $isValidStation = !$this->isCollidingWithOtherStations($x, $y, $minDistance, $regionId, $reservedRegions, $universeService);
                        $isValidAsteroid = !$universeService->isCollidingWithAsteroid($x, $y, $asteroidMinDistance);

                        if ($isValidStation && $isValidAsteroid) {
                            $isValid = true;
                            $validPositions++;
                            $validRegions[] = [
                                'region' => $index + 1,
                                'x' => (int) $x,
                                'y' => (int) $y,
                            ];
                        }
                    } catch (\Exception $e) {
                        if ($debug) {
                            $this->error("Fehler in Region {$index}: " . $e->getMessage());
                        }
                    }

                    if (!$isValid) {
                        $invalidPositions++;
                        // Zusätzliche Informationen speichern, warum die Region ungültig ist
                        $isStationCollision = $this->isCollidingWithOtherStations($x, $y, $minDistance, $regionId, $reservedRegions, $universeService);
                        $isAsteroidCollision = $universeService->isCollidingWithAsteroid($x, $y, $asteroidMinDistance);

                        $invalidRegions[] = [
                            'region' => $index + 1,
                            'x' => (int) $x,
                            'y' => (int) $y,
                            'radius' => (int) ($region['inner_radius'] ?? $region['radius'] ?? 0),
                            'station_collision' => $isStationCollision ? 'Ja' : 'Nein',
                            'asteroid_collision' => $isAsteroidCollision ? 'Ja' : 'Nein'
                        ];
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine(2);

                // Ergebnisse anzeigen
                $this->info("Ergebnis der Regionsprüfung:");
                $this->info("- Gültige Regionen: {$validPositions} (" . round($validPositions / count($reservedRegions) * 100, 1) . "%)");
                $this->info("- Ungültige Regionen: {$invalidPositions} (" . round($invalidPositions / count($reservedRegions) * 100, 1) . "%)");

                // Beispiele für gültige Standorte anzeigen
                if (count($validRegions) > 0) {
                    $this->info("Beispiele für gültige Standorte in Regionen:");

                    $displayCount = $showAll ? count($validRegions) : min(10, count($validRegions));

                    $this->table(
                        ['Region #', 'X', 'Y'],
                        array_slice($validRegions, 0, $displayCount)
                    );

                    if (!$showAll && count($validRegions) > 10) {
                        $this->info("(Es werden nur die ersten 10 Standorte angezeigt von insgesamt {$validPositions})");
                    }
                }

                // Zeige die ungültigen Regionen an
                if (count($invalidRegions) > 0) {
                    $this->info("Ungültige Regionen (keine Station platzierbar):");

                    $displayCount = $showAll ? count($invalidRegions) : min(10, count($invalidRegions));

                    $this->table(
                        ['Region #', 'Zentrum X', 'Zentrum Y', 'Radius', 'Stations-Kollision', 'Asteroiden-Kollision'],
                        array_slice($invalidRegions, 0, $displayCount)
                    );

                    if (!$showAll && count($invalidRegions) > 10) {
                        $this->info("(Es werden nur die ersten 10 ungültigen Regionen angezeigt von insgesamt {$invalidPositions})");
                    }
                }

                return 0;
            }
        }

        // Methode zum Prüfen potenzieller Stationsstandorte implementieren
        $this->info("Suche {$count} potenzielle Stationsstandorte...");

        $startTime = microtime(true);

        // Potenzielle Stationsstandorte finden
        $spawns = $this->findPotentialStationSpawns($count, $universeService, $asteroidGenerator);

        $duration = microtime(true) - $startTime;

        $this->info("Gefunden: " . count($spawns) . " potenzielle Standorte in " . number_format($duration, 2) . " Sekunden");

        if (count($spawns) > 0) {
            $this->info("\nBeispiel-Koordinaten:");

            $displayCount = $showAll ? count($spawns) : min(5, count($spawns));

            $this->table(
                ['#', 'X', 'Y', 'Region ID'],
                collect(array_slice($spawns, 0, $displayCount))->map(function ($spawn, $key) {
                    return [
                        '#' => $key + 1,
                        'X' => $spawn['x'],
                        'Y' => $spawn['y'],
                        'Region ID' => $spawn['region_id'] ?? 'N/A'
                    ];
                })
            );

            if (!$showAll && count($spawns) > 5) {
                $this->info("(Es werden nur die ersten 5 Standorte angezeigt von insgesamt " . count($spawns) . ")");
            }
        }

        return 0;
    }

    /**
     * Prüft, ob eine Position mit anderen Stationen außer der angegebenen kollidiert
     * 
     * @param int $x X-Koordinate
     * @param int $y Y-Koordinate
     * @param int $minDistance Minimaler Abstand zwischen Stationen
     * @param int|string $currentRegionId ID der aktuellen Region, die nicht geprüft werden soll
     * @param array $allRegions Alle Regionen
     * @param UniverseService $universeService Generator für zusätzliche Überprüfungen
     * @return bool True wenn eine Kollision vorliegt
     */
    private function isCollidingWithOtherStations(
        int $x,
        int $y,
        int $minDistance,
        $currentRegionId,
        array $allRegions,
        UniverseService $universeService
    ): bool {
        // Prüfen auf Kollisionen mit existierenden Stationen
        $stations = $universeService->getStations();
        foreach ($stations as $station) {
            $distance = sqrt(pow($station['x'] - $x, 2) + pow($station['y'] - $y, 2));
            if ($distance < $minDistance) {
                return true;
            }
        }

        // Prüfen auf Kollisionen mit ANDEREN reservierten Regionen
        foreach ($allRegions as $regionKey => $region) {
            $regionId = $region['id'] ?? $regionKey;

            // Die aktuelle Region überspringen
            if ($regionId == $currentRegionId) {
                continue;
            }

            $stationX = $region['station_x'] ?? $region['x'] ?? 0;
            $stationY = $region['station_y'] ?? $region['y'] ?? 0;
            $distance = sqrt(pow($stationX - $x, 2) + pow($stationY - $y, 2));

            if ($distance < $minDistance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Findet potenzielle Stationsstandorte
     * 
     * @param int $count Anzahl der zu findenden Standorte
     * @param UniverseService $universeService
     * @param AsteroidGenerator $asteroidGenerator
     * @return array Gefundene Standorte
     */
    private function findPotentialStationSpawns(
        int $count,
        UniverseService $universeService,
        AsteroidGenerator $asteroidGenerator
    ): array {
        $reservedRegions = $universeService->getReservedStationRegions();
        $validSpawns = [];
        $attemptsPerRegion = 30;
        $minDistance = config('game.core.station_distance');
        $asteroidMinDistance = config('game.core.asteroid_to_station_distance');

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        // Durchlaufe die reservierten Regionen und versuche, Standorte zu finden
        foreach ($reservedRegions as $regionId => $region) {
            if (count($validSpawns) >= $count) {
                break;
            }

            for ($attempt = 0; $attempt < $attemptsPerRegion; $attempt++) {
                // Zufälligen Punkt innerhalb der Region wählen
                $angle = rand(0, 360) * M_PI / 180;
                $radius = $region['inner_radius'] ?? $region['radius'] ?? 0;
                $distance = sqrt(rand(0, 100) / 100) * $radius;

                $x = $region['station_x'] ?? $region['x'] ?? 0;
                $y = $region['station_y'] ?? $region['y'] ?? 0;

                $x += $distance * cos($angle);
                $y += $distance * sin($angle);

                // Prüfen, ob hier eine Station platziert werden kann
                $isValidStation = !$asteroidGenerator->isCollidingWithStation($x, $y, $minDistance);
                $isValidAsteroid = !$asteroidGenerator->isCollidingWithAsteroid($x, $y, $asteroidMinDistance);

                if ($isValidStation && $isValidAsteroid) {
                    $validSpawns[] = [
                        'x' => (int) $x,
                        'y' => (int) $y,
                        'region_id' => $regionId
                    ];

                    $progressBar->advance();

                    if (count($validSpawns) >= $count) {
                        break 2; // Beide Schleifen beenden
                    }
                }
            }
        }

        $progressBar->finish();
        $this->newLine();

        return $validSpawns;
    }
}
