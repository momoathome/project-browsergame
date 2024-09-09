<?php

namespace App\Dto;

class Losses
{
    public function __construct(
        public readonly string $name,
        public readonly int $quantity,
        public readonly int $losses
    ) {}
}
