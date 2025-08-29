<?php

namespace Orion\Modules\Combat\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Combat\Services\CombatService;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Combat\Services\CombatOrchestrationService;

class CombatController extends Controller
{
    public function __construct(
        private readonly CombatService $combatService,
        private readonly CombatOrchestrationService $combatOrchestrationService,
        private readonly SpacecraftService $spacecraftService,
        private readonly AuthManager $authManager,
        private readonly StationService $stationService
    ) {
    }

    /**
     * Zeigt die Kampfsimulator-Seite an
     */
    public function index()
    {
        $user = $this->authManager->user();
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($user->id);

        return Inertia::render('Simulator', [
            'spacecrafts' => $spacecrafts,
            'result' => []
        ]);
    }

    /**
     * Simuliert einen Kampf ohne ihn tatsächlich auszuführen
     */
    public function simulate(Request $request)
    {
        $attacker = $request->input('attacker');
        $defender = $request->input('defender');

        $result = $this->combatService->simulateBattle($attacker, $defender, null);

        return Inertia::render('Simulator', [
            'result' => $result,
        ]);
    }

    /**
     * Plant einen Kampf und fügt ihn zur Warteschlange hinzu
     */
    public function combat(Request $request)
    {
        $user = $this->authManager->user();

        $validated = $request->validate([
            'station_user_id' => 'required|exists:users,id',
            'spacecrafts' => 'required|array',
        ]);

        $defender_id = $validated['station_user_id'];
        $defenderStation = $this->stationService->findStationByUserId($defender_id);
        
        // Führe den Kampfplan durch
        $this->combatOrchestrationService->planAndQueueCombat(
            $user,
            $defender_id,
            $validated['spacecrafts'],
            $defenderStation
        );
    }

}
