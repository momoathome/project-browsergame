<?php

namespace App\Http\Controllers;

use App\Services\BattleCalculation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BattleController extends Controller
{
  private $battleService;

  public function __construct(BattleCalculation $battleService)
  {
    $this->battleService = $battleService;
  }

  public function index()
  {
    return Inertia::render('Simulator', [
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
