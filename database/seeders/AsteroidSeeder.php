<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\AsteroidGenerator;
use Illuminate\Support\Facades\DB;


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

        $this->command->info("{$count} Asteroiden wurden erstellt.");
    }
}

