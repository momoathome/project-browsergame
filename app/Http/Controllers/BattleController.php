<?php

namespace App\Http\Controllers;

use App\Services\BattleCalculation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Spacecraft;

class BattleController extends Controller
{
  private $battleService;

  public function __construct(BattleCalculation $battleService)
  {
    $this->battleService = $battleService;
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
    $validated = $request->validate([
      'station_user_id' => 'required|exists:users,id',
      'spacecrafts' => 'required|array',
    ]);

    $defender_id = $validated['station_user_id'];

    $defender_spacecrafts = Spacecraft::with('details')
      ->where('user_id', $defender_id)
      ->orderBy('id', 'asc')
      ->get();

    $attacker_formatted = collect($validated['spacecrafts'])->map(function ($count, $name) {
      $spacecraft = Spacecraft::where('user_id', auth()->id())
        ->whereHas('details', function ($query) use ($name) {
          $query->where('name', $name);
        })
        ->first();

      return [
        'name' => $name,
        'combat' => $spacecraft->combat,
        'count' => $count,
      ];
    })->values()->toArray();

    $defender_formatted = $defender_spacecrafts->map(function ($spacecraft) {
      return [
        'name' => $spacecraft->details->name,
        'combat' => $spacecraft->combat,
        'count' => $spacecraft->count,
      ];
    })->toArray();

    $result = $this->battleService->simulateBattle($attacker_formatted, $defender_formatted);

    return response()->json($result);
  }

}
