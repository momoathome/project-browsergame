<?php

namespace Orion\Modules\Combat\Dto;

class Spacecraft
{
    public function __construct(
        public readonly string $name,
        public readonly int $combat,
        public readonly int $count,
        // public readonly int $cargo
    ) {}
}
