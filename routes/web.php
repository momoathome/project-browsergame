<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\SpacecraftController;
use App\Http\Controllers\MarketController;

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
    Route::post('/buildings/{building}/update', [BuildingController::class, 'update'])->name('buildings.update');
    
    Route::get('/shipyard', [SpacecraftController::class, 'index'])->name('shipyard');
    Route::post('/shipyard/{spacecraft}/update', [SpacecraftController::class, 'update'])->name('shipyard.update');

    Route::get('/market', [MarketController::class, 'index'])->name('market');
    Route::post('/market/{resource}/update', [MarketController::class, 'update'])->name('market.update');

    Route::get('/logbook', function () {return Inertia::render('Logbook');})->name('logbook');
    Route::get('/research', function () {return Inertia::render('Research');})->name('research');
    Route::get('/starmap', function () {return Inertia::render('Starmap');})->name('starmap');
    
    Route::get('/simulator', function () {return Inertia::render('Simulator');})->name('simulator');

    Route::get('/admin/dashboard', function () {return Inertia::render('Admin/Dashboard');})->name('admin.dashboard');
});
