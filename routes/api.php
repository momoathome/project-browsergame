<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsteroidController;
use App\Http\Controllers\GameController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// Route::post('/start-mining', [GameController::class, 'startMining']);
// Route::get('/player-queue', [GameController::class, 'getPlayerQueue']);
// oder in der entsprechenden API-Routendatei
// Route::post('/calculate-mining-duration', [AsteroidController::class, 'calculateMiningDuration'])
//     ->name('asteroidMap.calculateMiningDuration');
