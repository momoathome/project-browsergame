<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;

class TestAsteroids extends Command
{
    protected $signature = 'game:test-asteroids 
                            {--count=3 : Anzahl der Asteroiden pro Größe/Faktor}
                            {--debug : Debug-Ausgabe aktivieren}';

    protected $description = 'Erstellt Test-Asteroiden ohne sie in die DB zu speichern und liefert Statistiken pro Pool/Größe/Faktor';

    public function handle()
    {
        $count = (int)$this->option('count');
        $debug = $this->option('debug');

        $generator = app(AsteroidGenerator::class);
        $generator->debug = $debug;

        $sizes = ['small', 'medium', 'large', 'extreme'];
        $factors = ['min', 'avg', 'max'];
        $config = config('game.asteroids.asteroid_faktor');

        $allStats = [];

        foreach ($sizes as $size) {
            $multiplierConfig = config("game.asteroids.asteroid_faktor_multiplier.{$size}");

            foreach ($factors as $factorOption) {
                $resourcesAggregate = [];

                for ($i = 0; $i < $count; $i++) {
                    // Base/Multiplier setzen
                    switch ($factorOption) {
                        case 'min':
                            $base = $config['min'];
                            $multiplier = $multiplierConfig['min'];
                            break;
                        case 'max':
                            $base = $config['max'];
                            $multiplier = $multiplierConfig['max'];
                            break;
                        case 'avg':
                            $base = intval(($config['min'] + $config['max']) / 2);
                            $multiplier = intval(($multiplierConfig['min'] + $multiplierConfig['max']) / 2);
                            break;
                    }

                    $asteroid = $generator->generateAsteroid($size);
                    $asteroid['base'] = $base;
                    $asteroid['multiplier'] = $multiplier;
                    $asteroid['value'] = floor($base * $multiplier);

                    $resources = $generator->generateResourcesFromPools($asteroid['value'], $size);

                    // Ressourcen sammeln
                    foreach ($resources as $res => $val) {
                        $resourcesAggregate[$res][] = $val;
                    }
                }

                // Statistiken berechnen
                $stats = [];
                // ...existing code...
                foreach ($resourcesAggregate as $res => $vals) {
                    // Werte flach machen (falls verschachtelt)
                    $flatVals = [];
                    array_walk_recursive($vals, function($v) use (&$flatVals) {
                        if (is_numeric($v)) $flatVals[] = $v;
                    });
                
                    $stats[$res] = [
                        'min' => count($flatVals) > 0 ? min($flatVals) : 0,
                        'max' => count($flatVals) > 0 ? max($flatVals) : 0,
                        'avg' => count($flatVals) > 0 ? round(array_sum($flatVals) / count($flatVals), 1) : 0,
                    ];
                }
                // ...existing code...

                $allStats[$size][$factorOption] = $stats;
            }
        }

        // Ausgabe
        foreach ($allStats as $size => $factorData) {
            $this->line("=== Size: {$size} ===");
            foreach ($factorData as $factor => $stats) {
                $this->line("Factor: {$factor}");
                foreach ($stats as $res => $vals) {
                    $this->line(" - {$res}: min={$vals['min']}, max={$vals['max']}, avg={$vals['avg']}");
                }
                $this->line('');
            }
        }

        $this->info("Statistik-Test für Test-Asteroiden abgeschlossen.");
        return 0;
    }
}
