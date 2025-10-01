<?php

namespace Orion\Modules\Asteroid\Dto;

use Orion\Modules\Asteroid\Models\Asteroid;

class ExplorationResult
{
    public function __construct(
        public readonly array $resourcesExtracted,
        public readonly int $totalCargoCapacity,
        public readonly Asteroid $asteroid,
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

    public function getAsteroid(): Asteroid
    {
        return $this->asteroid;
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
