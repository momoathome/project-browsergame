<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\HandleExceptionsForJetstream;

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\SpacecraftController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\UserResourceController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AsteroidController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\OverviewController;
use App\Services\QueueService;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', HandleExceptionsForJetstream::class,])->group(function () {
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');

    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings');
    Route::post('/buildings/{building}/update', [BuildingController::class, 'update'])->name('buildings.update');

    Route::get('/shipyard', [SpacecraftController::class, 'index'])->name('shipyard');
    Route::post('/shipyard/{spacecraft}/update', [SpacecraftController::class, 'update'])->name('shipyard.update');
    Route::post('/shipyard/{spacecraft}/unlock', [SpacecraftController::class, 'unlock'])->name('shipyard.unlock');

    Route::get('/market', [MarketController::class, 'index'])->name('market');
    Route::post('/market/buy', [MarketController::class, 'buy'])->name('market.buy');
    Route::post('/market/sell', [MarketController::class, 'sell'])->name('market.sell');

    Route::get('/logbook', function () {
        return Inertia::render('Logbook'); })->name('logbook');
    Route::get('/research', function () {
        return Inertia::render('Research'); })->name('research');

    Route::get('/asteroidMap', [AsteroidController::class, 'index'])->name('asteroidMap');
    Route::post('/asteroidMap/update', [AsteroidController::class, 'update'])->name('asteroidMap.update');
    Route::post('/asteroidMap/combat', [BattleController::class, 'combat'])->name('asteroidMap.combat');
    Route::get('/asteroidMap/search', [AsteroidController::class, 'search'])->name('asteroidMap.search');
    Route::get('/asteroidMap/asteroid/{asteroid}', [AsteroidController::class, 'getAsteroidResources'])->name('asteroidMap.asteroid');

    Route::get('/simulator', [BattleController::class, 'index'])->name('simulator');
    Route::post('/simulator', [BattleController::class, 'simulate'])->name('simulator.simulate');

    Route::post('/resources/add', [UserResourceController::class, 'addResource'])->name('resources.add');

    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');    
    Route::get('/admin/user/{id}', [DashboardController::class, 'show'])->name('admin.user.show');
    Route::put('/admin/stations/{id}', [DashboardController::class, 'update'])->name('admin.stations.update');    
    Route::put('/admin/buildings/{id}', [DashboardController::class, 'updateBuilding'])->name('admin.buildings.update');
    Route::post('/admin/queue/finish/{userId}', [QueueService::class, 'processQueueForUserInstant'])->name('admin.queue.finish');

    Route::get('/images/{filename}', [ImageController::class, 'show']);

});
