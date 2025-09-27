<?php

namespace Orion\Modules\Combat\Dto;

class CombatRequest
{
    public function __construct(
        public readonly int $attackerId,
        public readonly int $defenderId,
        public readonly array $attackerSpacecrafts,
        public readonly array $defenderSpacecrafts,
        public readonly string $attackerName,
        public readonly string $defenderName,
        public readonly array $attackerCoordinates = [],
        public readonly array $targetCoordinates = [],
        public readonly bool $isRebelCombat = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['attacker_id'],
            $data['defender_id'],
            $data['attacker_formatted'],
            $data['defender_formatted'] ?? [],
            $data['attacker_name'],
            $data['defender_name'],
            $data['attacker_coordinates'] ?? [],
            $data['defender_coordinates'] ?? [],
            $data['is_rebel_combat'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'attacker_id' => $this->attackerId,
            'defender_id' => $this->defenderId,
            'attacker_formatted' => $this->attackerSpacecrafts,
            'defender_formatted' => $this->defenderSpacecrafts,
            'attacker_name' => $this->attackerName,
            'defender_name' => $this->defenderName,
            'attacker_coordinates' => $this->attackerCoordinates,
            'target_coordinates' => $this->targetCoordinates,
            'is_rebel_combat' => $this->isRebelCombat,
        ];
    }
}
