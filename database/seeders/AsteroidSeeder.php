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

    $this->command->info("{$count} Asteroids created.");
    $asteroidModel = "App\\Models\\Asteroid";
    Artisan::call('scout:flush', ['model' => $asteroidModel]);
    Artisan::call('scout:import', ['model' => $asteroidModel]);
    Artisan::call('scout:index', ['name' => 'asteroids']);
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    $this->command->info("Asteroids imported and indexed. In " . number_format($executionTime, 2) . " seconds.");
  }
}

