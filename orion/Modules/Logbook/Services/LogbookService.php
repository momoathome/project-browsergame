<?php

namespace Orion\Modules\Logbook\Services;

use Orion\Modules\Combat\Repositories\CombatRepository;
use Orion\Modules\Asteroid\Repositories\AsteroidRepository;


readonly class LogbookService
{
    public function __construct(
        private CombatRepository $combatRepository,
        private AsteroidRepository $asteroidRepository
    ) {
    }

    public function getRecentCombatsForUser(int $userId)
    {
        return $this->combatRepository->getRecentCombatsForUser($userId);
    }

    public function getRecentAsteroidMinesForUser(int $userId)
    {
        return $this->asteroidRepository->getRecentAsteroidMines($userId);
    }
}
