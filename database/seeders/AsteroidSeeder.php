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
    $this->config = config('asteroids');
  }
  public function run()
  {
    DB::table(table: 'asteroids')->truncate();

    $asteroidGenerator = app(AsteroidGenerator::class);

    $count = $this->config['asteroid_count'];

    $asteroidGenerator->generateAsteroids($count);

    $this->command->info("{$count} Asteroids created.");
    $asteroidModel = "App\\Models\\Asteroid";
    Artisan::call('scout:flush', ['model' => $asteroidModel]);
    Artisan::call('scout:import', ['model' => $asteroidModel]);
    Artisan::call('scout:index', ['name' => 'asteroids']);
    $this->command->info("Asteroids imported and indexed.");


  }
}

