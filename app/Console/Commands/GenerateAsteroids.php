<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AsteroidGenerator;

class GenerateAsteroids extends Command
{
    protected $signature = 'asteroids:generate';
    protected $description = 'Generates new asteroids';

    protected $asteroidGenerator;

    public function __construct(AsteroidGenerator $asteroidGenerator)
    {
        parent::__construct();
        $this->asteroidGenerator = $asteroidGenerator;
    }

    public function handle()
    {
        $this->asteroidGenerator->generateAsteroids(100);
        $this->info('Asteroids generated successfully!');
    }
}

