<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\AsteroidGenerator;
use Illuminate\Support\Facades\Artisan;

class AsteroidSeeder extends Seeder
{
  protected $config;

  public function __construct()
  {
    $this->config = config('game.asteroids');
  }
  public function run()
  {
    $startTime = microtime(true);

    DB::table(table: 'asteroids')->truncate();
    $asteroidGenerator = app(AsteroidGenerator::class);
    $count = $this->config['asteroid_count'];
    $asteroidGenerator->generateAsteroids($count);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    $this->command->info("{$count} Asteroids created in " . number_format($executionTime, 2) . " seconds.");
    $this->command->info("Indexing asteroids... depending on the amount of asteroids, this may take a few minutes.");

    // Index the asteroids 
    $startTime = microtime(true);
    $asteroidModel = "App\\Models\\Asteroid";
    Artisan::call('scout:flush', ['model' => $asteroidModel]);
    Artisan::call('scout:import', ['model' => $asteroidModel]);
    Artisan::call('scout:index', ['name' => 'asteroids']);
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    $this->command->info("Asteroids imported and indexed in " . number_format($executionTime, 2) . " seconds.");

    $this->command->info("Konfiguriere Meilisearch für optimale Suche...");
    Artisan::call('meilisearch:configure');
    $this->command->info("Meilisearch-Konfiguration abgeschlossen.");
  }
}

