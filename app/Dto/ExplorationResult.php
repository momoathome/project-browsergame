<?php

namespace App\Dto;

class ExplorationResult
{
    public function __construct(
        private array $resourcesExtracted,
        private int $totalCargoCapacity,
        private int $asteroidId,
        private bool $hasMiner
    ) {
    }

    public function getResourcesExtracted(): array
    {
        return $this->resourcesExtracted;
    }

    public function getTotalCargoCapacity(): int
    {
        return $this->totalCargoCapacity;
    }

    public function getAsteroidId(): int
    {
        return $this->asteroidId;
    }

    public function hasMiner(): bool
    {
        return $this->hasMiner;
    }

    public function wasSuccessful(): bool
    {
        return !empty($this->resourcesExtracted);
    }
}
