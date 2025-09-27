<?php

namespace Orion\Modules\Combat\Dto;

use App\Models\User;
use Illuminate\Support\Collection;
use Orion\Modules\Rebel\Models\Rebel;

class CombatPlanRequest
{
    public function __construct(
        public readonly User|Rebel $attacker,
        public readonly User|Rebel $defender,
        public readonly array $spacecrafts,
        public readonly bool $isRebelCombat = false,
    ) {
    }

    public static function fromRequest(User|Rebel $attacker, User|Rebel $defender, array $spacecrafts): self
    {
        return new self(
            $attacker,
            $defender,
            $spacecrafts,
            $isRebelCombat = $defender instanceof Rebel
        );
    }
}
