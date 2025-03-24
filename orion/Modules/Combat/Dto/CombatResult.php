<?php

namespace Orion\Modules\Combat\Dto;

use Illuminate\Support\Collection;

class CombatResult
{
    public $attackerName;
    public $defenderName;
    
    public function __construct(
        public readonly string $winner,
        public readonly array $attackerLosses,
        public readonly array $defenderLosses
    ) {}

    /**
     * Gibt Verluste als Collection zurück, mit Schiffsnamen als Schlüssel
     */
    public function getLossesCollection(string $type = 'attacker'): Collection
    {
        $losses = $type === 'attacker' ? $this->attackerLosses : $this->defenderLosses;
        
        // Konvertiert die Losses-Objekte in eine Collection, die nach Namen indiziert ist
        return collect($losses)->keyBy(function($loss) {
            return $loss->name;
        });
    }

    public function wasSuccessful(): bool
    {
        return in_array($this->winner, ['attacker', 'defender']);
    }
}
