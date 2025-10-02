<?php

namespace Orion\Modules\Rebel\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Services\UserAttributeService;

class RebelDifficultyService
{
    public function __construct(
        private readonly UserAttributeService $userAttributeService,
    ) {
    }
    /**
     * Berechne die globale Difficulty anhand der Spielerpunkte.
     * Formel: pro 100 Punkte = +0.1 Difficulty
     */
    public function calculateGlobalDifficulty(): float
    {
        $totalPlayerInfluence = $this->userAttributeService->getTotalAttributeValueByType(UserAttributeType::INFLUENCE);
        $playerCount = DB::table('users')->count();
            
        $averageInfluence = $totalPlayerInfluence / $playerCount;

        return ($averageInfluence / 100) * 0.1;
    }

    /**
     * Berechne dynamisches Fleet Cap.
     */
    public function getFleetCap(Rebel $rebel, float $globalDifficulty): int
    {
        $base = $rebel->difficulty_level + $globalDifficulty;
        return intval(10 * pow($base, 1.05));
    }

    /**
     * Berechne dynamisches Resource Cap.
     */
    public function getResourceCap(Rebel $rebel, float $globalDifficulty): int
    {
        return intval(500 * ($rebel->difficulty_level + $globalDifficulty));
    }
}
