<?php

namespace App\Http\Controllers;

use App\Services\BattleCalculation;
use App\Services\AsteroidExplorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Spacecraft;
use App\Models\Station;
use App\Services\QueueService;
use App\Models\ActionQueue;
use App\Models\UserResource;
use App\Models\UserAttribute;
use Log;

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
    $defender = User::find($defenderId);
    $attacker_formatted = $details['attacker_formatted'];
    $defender_formatted = $details['defender_formatted'];

    $result = $this->battleService->simulateBattle($attacker_formatted, $defender_formatted);

    $result->attackerName = $details['attacker_name'];
    $result->defenderName = $details['defender_name'];

    $this->asteroidExplorer->freeSpacecrafts($attacker, $this->formatSpacecraftsForLocking($attacker_formatted));

    if ($result->winner === 'attacker') {
      // Implementierung für Ressourcenplünderung
      $this->transferResources($attacker, $defender, $attacker_formatted);
    }

    // Speichere das Kampfergebnis in der Datenbank oder sende eine Benachrichtigung
    // $this->saveBattleResult($result, $attackerId, $defenderId);

    return $result;
  }

  /**
   * Transferiert Ressourcen vom Verteidiger zum Angreifer nach einem gewonnenen Kampf
   */
  private function transferResources($attacker, $defender, $attackerSpacecrafts)
  {
    // Berechne die Ladekapazität der angreifenden Schiffe
    $totalCargoCapacity = $this->calculateTotalCargoCapacity($attacker, $this->formatSpacecraftsForLocking($attackerSpacecrafts));

    // Ermittle die Ressourcen des Verteidigers
    $defenderResources = UserResource::where('user_id', $defender->id)
      ->with('resource')
      ->get();

    // Berechne, wie viel geplündert werden kann (max. 80% der Ressourcen des Verteidigers)
    $resourcesAvailable = [];
    $remainingResources = [];

    foreach ($defenderResources as $userResource) {
      if ($userResource->amount > 0) {
        $maxPlunder = floor($userResource->amount * 0.8);
        $resourcesAvailable[$userResource->resource_id] = $maxPlunder;
        $remainingResources[$userResource->resource_id] = $userResource->amount - $maxPlunder;
      }
    }

    // Prüfe, ob die Gesamtmenge die Ladekapazität übersteigt
    $totalAvailable = array_sum($resourcesAvailable);
    $extractionRatio = $totalAvailable > $totalCargoCapacity ? $totalCargoCapacity / $totalAvailable : 1;

    // Berechne die tatsächlich zu transferierenden Ressourcen
    $resourcesExtracted = [];
    foreach ($resourcesAvailable as $resourceId => $amount) {
      $extractedAmount = floor($amount * $extractionRatio);
      if ($extractedAmount > 0) {
        $resourcesExtracted[$resourceId] = $extractedAmount;
        // Aktualisiere die verbleibenden Ressourcen des Verteidigers
        $remainingResources[$resourceId] = $defenderResources->firstWhere('resource_id', $resourceId)->amount - $extractedAmount;
      }
    }

    DB::transaction(function () use ($attacker, $defender, $resourcesExtracted, $remainingResources) {
      $this->updateAttackerResources($attacker, $resourcesExtracted);

      $this->updateDefenderResources($defender, $remainingResources);
    });

    return $resourcesExtracted;
  }

  /**
   * Berechnet die Gesamtladekapazität der Raumschiffe
   */
  private function calculateTotalCargoCapacity($user, $spacecrafts)
  {
    $totalCargoCapacity = 0;

    $spacecraftsWithDetails = $this->asteroidExplorer->getSpacecraftsWithDetails($user, $spacecrafts);

    foreach ($spacecraftsWithDetails as $spacecraft) {
      $amountOfSpacecrafts = $spacecrafts[$spacecraft->details->name];
      $totalCargoCapacity += $amountOfSpacecrafts * $spacecraft->cargo;
    }

    return $totalCargoCapacity;
  }

  /**
   * Aktualisiert die Ressourcen des Angreifers nach dem Plündern
   */
  private function updateAttackerResources($attacker, $resourcesExtracted)
  {
    $userStorageAttribute = UserAttribute::where('user_id', $attacker->id)
      ->where('attribute_name', 'storage')
      ->first();

    $storageCapacity = $userStorageAttribute->attribute_value;

    foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
      $userResource = UserResource::firstOrNew([
        'user_id' => $attacker->id,
        'resource_id' => $resourceId,
      ]);

      $availableStorage = $storageCapacity - $userResource->amount;
      $amountToAdd = min($extractedAmount, $availableStorage);

      $userResource->amount += $amountToAdd;
      $userResource->save();
    }
  }

  /**
   * Aktualisiert die Ressourcen des Verteidigers nach dem Plündern
   */
  private function updateDefenderResources($defender, $remainingResources)
  {
    foreach ($remainingResources as $resourceId => $remainingAmount) {
      $userResource = UserResource::where('user_id', $defender->id)
        ->where('resource_id', $resourceId)
        ->first();

      if ($userResource) {
        $userResource->amount = $remainingAmount;
        $userResource->save();
      }
    }
  }

}

