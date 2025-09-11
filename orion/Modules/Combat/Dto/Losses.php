<?php

namespace Orion\Modules\Combat\Dto;

class Losses
{
    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly int $losses,
        public readonly int $combat = 0
    ) {}
}
