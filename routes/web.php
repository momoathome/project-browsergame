<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\HandleExceptionsForJetstream;

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\SpacecraftController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\UserResourceController;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum',config('jetstream.auth_session'),'verified', HandleExceptionsForJetstream::class,])->group(function () {
    Route::get('/overview', function () {return Inertia::render('Overview');})->name('overview');
    
    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings');
    Route::post('/buildings/{building}/update', [BuildingController::class, 'update'])->name('buildings.update');
    
    Route::get('/shipyard', [SpacecraftController::class, 'index'])->name('shipyard');
    Route::post('/shipyard/produce', [SpacecraftController::class, 'produce'])->name('shipyard.produce');

    Route::get('/market', [MarketController::class, 'index'])->name('market');
    Route::post('/market/buy', [MarketController::class, 'buy'])->name('market.buy');
    Route::post('/market/sell', [MarketController::class, 'sell'])->name('market.sell');

    Route::get('/logbook', function () {return Inertia::render('Logbook');})->name('logbook');
    Route::get('/research', function () {return Inertia::render('Research');})->name('research');
    Route::get('/asteroidMap', function () {return Inertia::render('AsteroidMap');})->name('asteroidMap');
    
    Route::get('/simulator', function () {return Inertia::render('Simulator');})->name('simulator');

    Route::post('/resources/add', [UserResourceController::class, 'addResource'])->name('resources.add');
    
    Route::get('/admin/dashboard', function () {return Inertia::render('Admin/Dashboard');})->name('admin.dashboard');

    Route::get('/images/{filename}', [ImageController::class, 'show']);

});
