<?php

namespace Orion\Modules\Combat\Dto;

class Spacecraft
{
    public function __construct(
        public readonly string $name,
        public readonly int $attack,
        public readonly int $defense,
        public readonly int $count,
        // public readonly int $cargo
    ) {}
}
