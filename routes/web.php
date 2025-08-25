<?php

use App\Events\ReloadFrontendCanvas;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ImageController;
use App\Http\Middleware\HandleExceptionsForJetstream;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\Actionqueue\Http\Controllers\ActionQueueController;
use Orion\Modules\Admin\Http\Controllers\AdminController;
use Orion\Modules\Combat\Http\Controllers\CombatController;
use Orion\Modules\Market\Http\Controllers\MarketController;
use Orion\Modules\Asteroid\Http\Controllers\AsteroidController;
use Orion\Modules\Building\Http\Controllers\BuildingController;
use Orion\Modules\Spacecraft\Http\Controllers\SpacecraftController;
use Orion\Modules\User\Http\Controllers\UserResourceController;
use Orion\Modules\User\Http\Controllers\OverviewController;

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
    Route::post('/market/{marketRes}/buy', [MarketController::class, 'buy'])->name('market.buy');
    Route::post('/market/{marketRes}/sell', [MarketController::class, 'sell'])->name('market.sell');

    Route::get('/logbook', function () {
        return Inertia::render('Logbook'); })->name('logbook');
    Route::get('/research', function () {
        return Inertia::render('Research'); })->name('research');

    Route::group([
        'prefix' => 'asteroidMap'
    ], function(){
        Route::get('/', [AsteroidController::class, 'index'])->name('asteroidMap');
        Route::post('/update', [AsteroidController::class, 'update'])->name('asteroidMap.update');
        Route::post('/combat', [CombatController::class, 'combat'])->name('asteroidMap.combat');
        Route::post('/search', [AsteroidController::class, 'search'])->name('asteroidMap.search');
        Route::post('/asteroid/{asteroid}', [AsteroidController::class, 'getAsteroidResources'])->name('asteroidMap.asteroid');
    });    

    Route::get('/simulator', [CombatController::class, 'index'])->name('simulator');
    Route::post('/simulator', [CombatController::class, 'simulate'])->name('simulator.simulate');

    Route::post('/resources/add', [UserResourceController::class, 'addResource'])->name('resources.add');

    Route::group([
        'middleware' => ['role:admin'],
        'prefix' => 'admin'
    ], function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/user/{id}', [AdminController::class, 'show'])->name('admin.user.show');
        Route::put('/stations/{id}', [AdminController::class, 'updateStation'])->name('admin.stations.update');
        Route::put('/buildings/{id}', [AdminController::class, 'updateBuilding'])->name('admin.buildings.update');
        Route::put('/resources/{id}', [UserResourceController::class, 'updateResourceAmount'])->name('admin.resources.update');
        Route::put('/spacecrafts/{id}', [AdminController::class, 'updateSpacecraft'])->name('admin.spacecrafts.update');
        Route::post('/spacecrafts/unlock', [AdminController::class, 'adminUnlock'])->name('admin.spacecrafts.unlock');
        Route::put('/market/{id}', [MarketController::class, 'update'])->name('admin.market.update');
        Route::post('/queue/finish/{userId}', [ActionQueueService::class, 'processQueueForUserInstant'])->name('admin.queue.finish');
        Route::post('/asteroids/regenerate/{amount}', [AdminController::class, 'adminRegenerateAsteroids'])->name('admin.asteroids.regenerate');
        Route::get('/progression', [AdminController::class, 'progression'])->name('admin.progression');
    });

    Route::patch('/queueProcess', [ActionQueueController::class, 'index'])->name('queue.process');

    Route::get('/images/{filename}', [ImageController::class, 'show']);

});
