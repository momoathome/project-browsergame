<?php

namespace Orion\Modules\Logbook\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Logbook\Services\LogbookService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class LogbookController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly SpacecraftService $spacecraftService,
        private readonly LogbookService $logbookService
    ) {
    }

    public function index()
    {
        $user = $this->authManager->user();
        $combatLogs = $this->logbookService->getRecentCombatsForUser($user->id);
        $asteroidMinesLogs = $this->logbookService->getRecentAsteroidMinesForUser($user->id);

        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($user->id);

        return Inertia::render('Logbook', [
            'combatLogs' => $combatLogs,
            'asteroidMinesLogs' => $asteroidMinesLogs,
            'spacecrafts' => $spacecrafts,
        ]);
    }

}
