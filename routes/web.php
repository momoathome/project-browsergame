<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ImageController;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\Admin\Controllers\AdminController;
use App\Http\Middleware\HandleExceptionsForJetstream;
use Orion\Modules\Combat\Http\Controllers\CombatController;
use Orion\Modules\Market\Http\Controllers\MarketController;
use Orion\Modules\User\Http\Controllers\OverviewController;
use Orion\Modules\Asteroid\Http\Controllers\AsteroidController;
use Orion\Modules\Building\Http\Controllers\BuildingController;
use Orion\Modules\User\Http\Controllers\UserResourceController;
use Orion\Modules\Spacecraft\Http\Controllers\SpacecraftController;

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

    Route::get('/logbook', function () {return Inertia::render('Logbook');})->name('logbook');
    Route::get('/research', function () {return Inertia::render('Research');})->name('research');

    Route::get('/asteroidMap', [AsteroidController::class, 'index'])->name('asteroidMap');
    Route::post('/asteroidMap/update', [AsteroidController::class, 'update'])->name('asteroidMap.update');
    Route::post('/asteroidMap/combat', [CombatController::class, 'combat'])->name('asteroidMap.combat');
    Route::get('/asteroidMap/search', [AsteroidController::class, 'search'])->name('asteroidMap.search');
    Route::get('/asteroidMap/asteroid/{asteroid}', [AsteroidController::class, 'getAsteroidResources'])->name('asteroidMap.asteroid');

    Route::get('/simulator', [CombatController::class, 'index'])->name('simulator');
    Route::post('/simulator', [CombatController::class, 'simulate'])->name('simulator.simulate');

    Route::post('/resources/add', [UserResourceController::class, 'addResource'])->name('resources.add');

    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/user/{id}', [AdminController::class, 'show'])->name('admin.user.show');
    Route::put('/admin/stations/{id}', [AdminController::class, 'update'])->name('admin.stations.update');
    Route::put('/admin/buildings/{id}', [AdminController::class, 'updateBuilding'])->name('admin.buildings.update');
    Route::put('/admin/resources/{id}', [UserResourceController::class, 'updateResourceAmount'])->name('admin.resources.update');
    Route::put('/admin/spacecrafts/{id}', [AdminController::class, 'updateSpacecraft'])->name('admin.spacecrafts.update');
    Route::post('/admin/queue/finish/{userId}', [QueueService::class, 'processQueueForUserInstant'])->name('admin.queue.finish');

    Route::get('/images/{filename}', [ImageController::class, 'show']);
    
});
