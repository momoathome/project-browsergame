<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\BuildingController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum',config('jetstream.auth_session'),'verified',])->group(function () {
    Route::get('/overview', function () {return Inertia::render('Overview');})->name('overview');
    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings');
    Route::get('/logbook', function () {return Inertia::render('Logbook');})->name('logbook');
    Route::get('/market', function () {return Inertia::render('Market');})->name('market');
    Route::get('/research', function () {return Inertia::render('Research');})->name('research');
    Route::get('/shipyard', function () {return Inertia::render('Shipyard');})->name('shipyard');
    Route::get('/starmap', function () {return Inertia::render('Starmap');})->name('starmap');
    Route::get('/starmap', function () {return Inertia::render('Starmap');})->name('starmap');
    
    Route::get('/simulator', function () {return Inertia::render('Simulator');})->name('simulator');

});
