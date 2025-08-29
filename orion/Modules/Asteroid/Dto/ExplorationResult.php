<?php

namespace Orion\Modules\Asteroid\Dto;

class ExplorationResult
{
    public function __construct(
        public readonly array $resourcesExtracted,
        public readonly int $totalCargoCapacity,
        public readonly int $asteroidId,
        public readonly bool $hasMiner
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
