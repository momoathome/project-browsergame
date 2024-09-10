<?php

namespace App\Dto;

class BattleResult
{
    public function __construct(
        public readonly string $winner,
        public readonly array $attackerLosses,
        public readonly array $defenderLosses
    ) {}

    public function toArray(): array
    {
        return [
            'winner' => $this->winner,
            'attackerLosses' => array_map(fn($loss) => [
                'name' => $loss->name,
                'count' => $loss->count,
                'losses' => $loss->losses
            ], $this->attackerLosses),
            'defenderLosses' => array_map(fn($loss) => [
                'name' => $loss->name,
                'count' => $loss->count,
                'losses' => $loss->losses
            ], $this->defenderLosses)
        ];
    }
}
