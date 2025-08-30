<?php

namespace Orion\Modules\Admin\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Orion\Modules\Market\Models\Market;
use Orion\Modules\User\Services\ResetUserData;
use Orion\Modules\Market\Services\MarketService;
use Orion\Modules\Station\Services\StationService;
use Orion\Modules\Building\Services\BuildingService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\Market\Services\SetupInitialMarket;
use Orion\Modules\User\Services\UserAttributeService;
use Orion\Modules\Asteroid\Services\AsteroidGenerator;
use Orion\Modules\Spacecraft\Services\SpacecraftService;
use Orion\Modules\Building\Services\BuildingUpgradeService;
use Orion\Modules\Spacecraft\Services\SpacecraftProductionService;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class AdminController extends Controller
{

    public function __construct(
        private readonly BuildingUpgradeService $buildingUpgradeService,
        private readonly BuildingService $buildingService,
        private readonly UserService $userService,
        private readonly StationService $stationService,
        private readonly SpacecraftService $spacecraftService,
        private readonly UserResourceService $userResourceService,
        private readonly UserAttributeService $userAttributeService,
        private readonly MarketService $marketService,
        private readonly SpacecraftProductionService $spacecraftProductionService,
        private readonly AuthManager $authManager,
        private readonly ResetUserData $resetUserData,
        private readonly SetupInitialMarket $setupInitialMarket,
        private readonly ActionQueueService $actionQueueService,
    ) {
        if (!$this->authManager->user()->hasRole('admin')) {
            return redirect()->route('overview');
        }
    }

    public function index()
    {
        // get all users with their stations and spacecrafts
        $users = $this->userService->findAll();
        $market = $this->marketService->getMarketData();

        return Inertia::render('Admin/Dashboard', [
            'users' => $users,
            'market' => $market,
            'gameQueue' => $this->actionQueueService->getActionQueue(),
        ]);
    }

    public function progression()
    {
        // Gebäudetypen aus Enum sammeln
        $buildingTypes = [];
        foreach (\Orion\Modules\Building\Enums\BuildingType::cases() as $buildingType) {
            $effectConfig = $buildingType->getEffectConfiguration();
            $buildingTypes[] = [
                'name' => $buildingType->value,
                'effect' => $buildingType->getEffectAttributes()[0] ?? null,
                'effectType' => $effectConfig['type']->value ?? 'MULTIPLICATIVE',
            ];
        }

        // Holen der Konfigurationsdaten aus den PHP-Config-Files
        $progressionData = [
            'buildTimeMultiplier' => config('game.building_progression.build_time_multiplier'),
            'growthFactors' => config('game.building_progression.growth_factors'),
            'milestoneMultipliers' => config('game.building_progression.milestone_multipliers'),
            'buildingConfigs' => [],
            'baseCosts' => [],
        ];

        // Building-Konfigurationen aus BuildingType-Enum übernehmen
        foreach (\Orion\Modules\Building\Enums\BuildingType::cases() as $buildingType) {
            $effectConfig = $buildingType->getEffectConfiguration();
            $progressionData['buildingConfigs'][$buildingType->value] = [
                'baseValue' => $effectConfig['base_value'],
                'increment' => $effectConfig['increment'],
                'type' => $effectConfig['type']->value,
            ];
        }

        // Basis-Kosten aus der buildings.php-Konfiguration laden
        $buildings = config('game.buildings.buildings');
        foreach ($buildings as $building) {
            $costs = collect($building['costs'])->map(function ($cost) {
                return [
                    'resource' => $cost['resource_name'],
                    'amount' => $cost['amount'],
                ];
            })->toArray();

            $progressionData['baseCosts'][$building['name']] = $costs;
        }

        // Ressourcenanforderungen nach Level laden
        $resourceRequirements = config('game.building_progression.building_resources');

        return Inertia::render('Admin/BuildingProgression', [
            'buildingTypes' => $buildingTypes,
            'progressionData' => $progressionData,
            'resourceRequirements' => $resourceRequirements,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // show specific user with their stations and spacecrafts
        $user = User::with('stations')
            ->find($id);

        $buildings = $this->buildingService->formatBuildingsForDisplay($id);
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserIdWithDetails($id);
        $resources = $this->userResourceService->getAllUserResourcesByUserId($id);
        $attributes = $this->userAttributeService->getAllUserAttributesByUserId($id);

        return Inertia::render('Admin/UserDetail', [
            'user' => $user,
            'buildings' => $buildings,
            'spacecrafts' => $spacecrafts,
            'resources' => $resources,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStation(Request $request, string $id)
    {
        $station = $this->stationService->findStationById($id);
        $station->update([
            'x' => $request->x,
            'y' => $request->y,
        ]);
    }

    public function updateBuilding(Request $request)
    {
        $request->validate([
            'building_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $buildingId = $request->building_id;
        $userId = $request->user_id;

        $result = $this->buildingUpgradeService->completeUpgrade($buildingId, $userId);

        if ($result) {
            return redirect()->back()->with('message', 'Gebäude wurde erfolgreich aufgewertet');
        } else {
            return redirect()->back()->with('error', 'Fehler beim Aufwerten des Gebäudes');
        }
    }

    public function updateSpacecraft(Request $request, $id)
    {
        $request->validate([
            'count' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $spacecraft = $this->spacecraftService->findSpacecraftById($id, $request->user_id);
        $spacecraft->update([
            'count' => $request->count,
            'user_id' => $request->user_id,
        ]);

        return redirect()->back()->with('message', 'Raumschiff erfolgreich aktualisiert');
    }

    public function adminUnlock(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'spacecraft_id' => 'required|integer',
        ]);

        $userId = $validated['user_id'];
        $spacecraftId = $validated['spacecraft_id'];

        $result = $this->spacecraftProductionService->adminUnlockSpacecraft($userId, $spacecraftId);

        if ($result) {
            return redirect()->back()->with('message', 'Raumschiff erfolgreich freigeschaltet');
        } else {
            return redirect()->back()->with('error', 'Fehler beim Freischalten des Raumschiffs');
        }
    }

    public function adminRegenerateAsteroids(Request $request, AsteroidGenerator $asteroidGenerator)
    {
        $result = $asteroidGenerator->regenerateAsteroids($request->input('count'));
        return redirect()->back()->with('message', $result['message']);
    }

    public function resetUserData(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);
        if ($user) {
            $this->resetUserData->resetUserData($user);
            return redirect()->back()->with('message', 'Benutzerdaten zurückgesetzt');
        }
        return redirect()->back()->with('error', 'Benutzer nicht gefunden');
    }

    public function resetAllUsersData()
    {
        $users = User::all();
        foreach ($users as $user) {
            $this->resetUserData->resetUserData($user);
        }
        return redirect()->back()->with('message', 'Alle Benutzerdaten zurückgesetzt');
    }

    public function resetMarketData()
    {
        Market::query()->delete();
        $this->setupInitialMarket->create();
        return redirect()->back()->with('message', 'Alle Marktdaten zurückgesetzt');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
