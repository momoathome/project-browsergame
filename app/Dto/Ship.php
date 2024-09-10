<?php

namespace App\Dto;

class Ship
{
    public function __construct(
        public readonly string $name,
        public readonly int $combatPower,
        public readonly int $count
    ) {}
}
