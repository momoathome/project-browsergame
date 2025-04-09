<?php

namespace Orion\Modules\Combat\Dto;

use App\Models\User;
use Illuminate\Support\Collection;

class CombatPlanRequest
{
    public function __construct(
        public readonly User $attacker,
        public readonly User $defender,
        public readonly array $spacecrafts,
        public readonly Collection $defenderSpacecrafts
    ) {
    }

    public static function fromRequest(User $attacker, User $defender, array $spacecrafts, Collection $defenderSpacecrafts): self
    {
        return new self(
            $attacker,
            $defender,
            $spacecrafts,
            $defenderSpacecrafts
        );
    }
}
