<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Orion\Modules\Asteroid\Services\UniverseService;

class ReserveStationRegions extends Command
{
    protected $signature = 'universe:reserve-regions 
                            {count=100 : Anzahl der zu reservierenden Regionen}
                            {--refresh : Cache zurücksetzen und neu generieren}';

    protected $description = 'Reserviert Regionen im Universum für zukünftige Stationsspawns';

    public function handle(UniverseService $universeService)
    {
        $count = $this->argument('count');
        $refresh = $this->option('refresh');
        
        if ($refresh) {
            $this->info("Cache für reservierte Regionen wird geleert...");
            Cache::forget('asteroid-generator:reserved-station-regions');
        }
        
        $this->info("Reserviere {$count} Regionen für Stationsspawns...");
        
        $startTime = microtime(true);
        $regions = $universeService->reserveStationRegions($count);
        $duration = microtime(true) - $startTime;
        
        $this->info("Erfolgreich " . count($regions) . " Regionen in " . number_format($duration, 4) . " Sekunden reserviert.");
        
        // Einfache Statistik zu den Regionen
        $totalArea = 0;
        foreach ($regions as $region) {
            // Benutze outer_radius für die Gesamtfläche 
            $radius = $region['outer_radius'] ?? $region['radius'] ?? 0;
            $totalArea += M_PI * $radius * $radius;
        }
        
        $universeSize = config('game.core.size');
        $universeArea = $universeSize * $universeSize;
        $percentCovered = ($totalArea / $universeArea) * 100;
        
        $this->info("Gesamtfläche der reservierten Regionen: " . number_format($totalArea) . " Einheiten²");
        $this->info("Das entspricht " . number_format($percentCovered, 2) . "% der Gesamtfläche des Universums.");
        
        // Zeige die ersten 10 Regionen zur Überprüfung
        $this->info("\nBeispiel-Regionen:");
        $this->table(
            ['#', 'X', 'Y', 'Inner Radius', 'Outer Radius'],
            collect(array_slice($regions, 0, 10))->map(function ($region, $key) {
                return [
                    '#' => $key + 1,
                    'X' => $region['station_x'] ?? $region['x'] ?? 0,
                    'Y' => $region['station_y'] ?? $region['y'] ?? 0,
                    'Inner Radius' => $region['inner_radius'] ?? '-',
                    'Outer Radius' => $region['outer_radius'] ?? $region['radius'] ?? 0
                ];
            })
        );
        
        return 0;
    }
}
