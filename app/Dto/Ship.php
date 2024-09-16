<?php

namespace App\Dto;

class Ship
{
    public function __construct(
        public readonly string $name,
        public readonly int $combat,
        public readonly int $count,
        // public readonly int $cargo
    ) {}
}
