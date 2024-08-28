<?php

namespace App\Http\Controllers;

use App\Models\Asteroid;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Spacecraft;
use App\Models\UserResource;
use App\Models\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AsteroidController extends Controller
{
    public function index()
    {
        $asteroids = Asteroid::all();
        $user = auth()->user();


        foreach ($asteroids as $asteroid) {
            $asteroid->resources = json_decode($asteroid->resources, true);
        }

        $spacecrafts = Spacecraft::with('details')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('AsteroidMap', [
            'asteroids' => $asteroids,
            'spacecrafts' => $spacecrafts,
        ]);
    }

    public function store(Request $request)
    {
        // Validierung der Eingabedaten
        $request->validate([
            'name' => 'required|string',
            'rarity' => 'required|string',
            'base' => 'required|numeric',
            'multiplier' => 'required|numeric',
            'value' => 'required|integer',
            'resources' => 'required|array',
            'x' => 'required|integer',
            'y' => 'required|integer',
            'pixel_size' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['resources'] = json_encode($data['resources']);

        $asteroid = Asteroid::create($data);

        return response()->json($asteroid, 201);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'asteroid_id' => 'required|exists:asteroids,id',
            'spacecrafts' => 'required|array',
        ]);

        $user = Auth::user();
        $spacecraftInPost = $validated['spacecrafts'];
        $filteredSpacecraftWithCount = array_filter($spacecraftInPost, function ($count) {
            return $count > 0;
        });
        $totalCargoCapacity = 0;
        $hasMiner = false;

        $spacecrafts = Spacecraft::join('spacecraft_details', 'spacecrafts.details_id', '=', 'spacecraft_details.id')
            ->where('user_id', $user->id)
            ->whereIn('spacecraft_details.name', array_keys($filteredSpacecraftWithCount))
            ->get();

        foreach ($spacecrafts as $spacecraft) {
            $spacecraftName = $spacecraft->details->name;
            $count = $filteredSpacecraftWithCount[$spacecraftName];
            $totalCargoCapacity += $count * $spacecraft->cargo;

            if ($spacecraft->details->type === 'Miner') {
                $hasMiner = true;
                Log::warning('if Miner: ' . $spacecraft->details->type);
            }
        }

        $asteroid = Asteroid::findOrFail($validated['asteroid_id']);
        $asteroidResources = json_decode($asteroid->resources, true);
        $resourceCount = count($asteroidResources);

        $resourcesExtracted = [];
        $remainingResources = [];

        foreach ($asteroidResources as $resourceName => $amount) {
            $resource = Resource::where('name', $resourceName)->first();
            $resourceId = $resource->id;

            $extractionMultiplier = $hasMiner ? 1 : 0.5;
            $extractedAmount = min($amount, floor($totalCargoCapacity / $resourceCount * $extractionMultiplier));
            $remainingAmount = max(0, $amount - $extractedAmount);

            $resourcesExtracted[$resourceId] = $extractedAmount;
            $remainingResources[$resourceName] = $remainingAmount;
        }

        DB::transaction(function () use ($asteroid, $remainingResources, $user, $resourcesExtracted) {
            foreach ($resourcesExtracted as $resourceId => $extractedAmount) {
                $userResource = UserResource::firstOrNew([
                    'user_id' => $user->id,
                    'resource_id' => $resourceId,
                ]);

                $userResource->count += $extractedAmount;
                $userResource->save();
            }

            if (empty(array_filter($remainingResources))) {
                $asteroid->delete();
            } else {
                $asteroid->resources = json_encode($remainingResources);
                $asteroid->save();
            }
        });

        return redirect()->route('asteroidMap')->banner('Asteroid explored successfully' . $hasMiner . ' ' . $totalCargoCapacity . json_encode($resourcesExtracted));
    }

}

