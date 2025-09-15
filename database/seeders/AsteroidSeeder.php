<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Orion\Modules\Asteroid\Services\UniverseService;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;

class AsteroidSeeder extends Seeder
{
  public function __construct(
    private readonly UniverseService $universeService,
    private readonly AsteroidGenerator $asteroidGenerator
  ) {
    $this->initialize();
  }

  private array $config = [];

  private function initialize(): void
  {
    $this->config = config('game.core');
  }

  public function run()
  {
    $startTime = microtime(true);

    $this->universeService->reserveStationRegions($this->config['default_stations'], true);

    // Strategische Anfänger-Asteroiden erzeugen
    $this->asteroidGenerator->generateStrategicLowValueAsteroids(
        $this->config['strategic_asteroid_count'],
        $this->config['strategic_asteroid_outer_radius']
    );

    // Dann Asteroiden generieren
    $count = $this->config['asteroid_count'];
    $this->command->info("Generate {$count} Asteroids...");
    $this->asteroidGenerator->generateAsteroids($count);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    $this->command->info("{$count} Asteroids created in " . number_format($executionTime, 2) . " seconds.");
    // $this->command->info("Indexing asteroids... depending on the amount of asteroids, this may take a few minutes.");

    // Index the asteroids 
/*     $startTime = microtime(true);
    $asteroidModel = "Orion\\Modules\\Asteroid\\Models\\Asteroid";
    Artisan::call('scout:flush', ['model' => $asteroidModel]);
    Artisan::call('scout:import', ['model' => $asteroidModel]);
    Artisan::call('scout:index', ['name' => 'asteroids']);
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    $this->command->info("Asteroids imported and indexed in " . number_format($executionTime, 2) . " seconds.");

    $this->command->info("Configure Meilisearch for optimal search...");
    Artisan::call('meilisearch:configure');
    $this->command->info("Meilisearch configuration completed."); */
  }
}

