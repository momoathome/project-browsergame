<?php

namespace App\Http\Controllers;

use App\Services\BattleCalculation;
use App\Services\AsteroidExplorer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Spacecraft;
use App\Models\Station;
use App\Services\QueueService;
use App\Models\ActionQueue;

class BattleController extends Controller
{
  private $battleService;
  private $queueService;
  private $asteroidExplorer;

  public function __construct(BattleCalculation $battleService, QueueService $queueService, AsteroidExplorer $asteroidExplorer)
  {
    $this->battleService = $battleService;
    $this->queueService = $queueService;
    $this->asteroidExplorer = $asteroidExplorer;
  }

  public function index()
  {

    $user = auth()->user();

    $spacecrafts = Spacecraft::with('details')
      ->where('user_id', $user->id)
      ->orderBy('id', 'asc')
      ->get();

    return Inertia::render('Simulator', [
      'spacecrafts' => $spacecrafts,
      'result' => []
    ]);
  }

  public function simulate(Request $request)
  {
    /*     
      name: spacecraft.details.name,
      combatPower: spacecraft.combat,
      count: 0
     */
    $attacker = $request->input('attacker');
    $defender = $request->input('defender');

    $result = $this->battleService->simulateBattle($attacker, $defender);

    return Inertia::render('Simulator', [
      'result' => $result,
    ]);
  }

  public function combat(Request $request)
  {
    $user = auth()->user();

    $validated = $request->validate([
      'station_user_id' => 'required|exists:users,id',
      'spacecrafts' => 'required|array',
    ]);

    $defender_id = $validated['station_user_id'];
    $defender = User::find($defender_id);
    $defenderStation = Station::where('user_id', $defender_id)->first();

    $defender_spacecrafts = Spacecraft::with('details')
      ->where('user_id', $defender_id)
      ->orderBy('id', 'asc')
      ->get();

    $attacker_formatted = collect($validated['spacecrafts'])
      ->map(function ($count, $name) use ($user) {
        $spacecraft = Spacecraft::where('user_id', $user->id)
          ->whereHas('details', function ($query) use ($name) {
            $query->where('name', $name);
          })
          ->first();

        return [
          'name' => $name,
          'combat' => $spacecraft ? $spacecraft->combat : 0,
          'count' => $count,
        ];
      })
      ->filter(function ($spacecraft) {
        return $spacecraft['count'] > 0;
      })
      ->values()
      ->toArray();

    $defender_formatted = $defender_spacecrafts->map(function ($spacecraft) {
      return [
        'name' => $spacecraft->details->name,
        'combat' => $spacecraft->combat,
        'count' => $spacecraft->count,
      ];
    })->toArray();

    $filteredSpacecrafts = $this->formatSpacecraftsForLocking($attacker_formatted);
    $spacecraftsWithDetails = $this->asteroidExplorer->getSpacecraftsWithDetails($user, $filteredSpacecrafts);

    $duration = $this->asteroidExplorer->calculateMiningDuration($spacecraftsWithDetails, $user, $defenderStation);

    $this->asteroidExplorer->lockSpacecrafts($user, $filteredSpacecrafts);

    $this->queueService->addToQueue(
      $user->id,
      ActionQueue::ACTION_TYPE_COMBAT,
      $defender_id,
      $duration,
      [
        'attacker_formatted' => $attacker_formatted,
        'defender_formatted' => $defender_formatted,
        'attacker_name' => $user->name,
        'defender_name' => $defender->name
      ]
    );

    return redirect()->route('asteroidMap');
  }

  private function formatSpacecraftsForLocking($spacecrafts)
  {
    $formatted = [];
    foreach ($spacecrafts as $spacecraft) {
      $formatted[$spacecraft['name']] = $spacecraft['count'];
    }
    return $formatted;
  }

  public function completeCombat($attackerId, $defenderId, $details)
  {
    $attacker = User::find($attackerId);
    $attacker_formatted = $details['attacker_formatted'];
    $defender_formatted = $details['defender_formatted'];

    $result = $this->battleService->simulateBattle($attacker_formatted, $defender_formatted);

    $result->attackerName = $details['attacker_name'];
    $result->defenderName = $details['defender_name'];

    $this->asteroidExplorer->freeSpacecrafts($attacker, $this->formatSpacecraftsForLocking($attacker_formatted));

    if ($result->winner === 'attacker') {
      // Implementierung für Ressourcenplünderung
      // $this->transferResources($attackerId, $defenderId);
    }

    // Speichere das Kampfergebnis in der Datenbank oder sende eine Benachrichtigung
    // $this->saveBattleResult($result, $attackerId, $defenderId);

    return $result;
  }

}
