<?php

namespace Orion\Modules\Combat\Dto;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CombatPlanRequest
{
    public function __construct(
        public readonly User $attacker,
        public readonly int $defenderId,
        public readonly string $defenderName,
        public readonly array $spacecrafts,
        public readonly Collection $defenderSpacecrafts
    ) {
    }
    
    public static function fromRequest(User $attacker, int $defenderId, string $defenderName, array $spacecrafts, Collection $defenderSpacecrafts): self
    {
        return new self(
            $attacker,
            $defenderId,
            $defenderName,
            $spacecrafts,
            $defenderSpacecrafts
        );
    }
}
