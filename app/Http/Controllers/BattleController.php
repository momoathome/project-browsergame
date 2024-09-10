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
    $attacker = $request->input('attacker');
    $defender = $request->input('defender');

    $result = $this->battleService->simulateBattle($attacker, $defender);

    return Inertia::render('Simulator', [
      'result' => $result,
    ]);
  }
}
