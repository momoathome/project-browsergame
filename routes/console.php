<?php

use App\Jobs\ProcessActionQueue;
use Illuminate\Foundation\Inspiring;
use App\Jobs\ProcessActionQueueBatch;
use Orion\Modules\Rebel\Models\Rebel;
use Illuminate\Support\Facades\Artisan;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use Orion\Modules\Rebel\Services\RebelResourceService;
use Orion\Modules\Rebel\Services\RebelDifficultyService;
use Orion\Modules\Rebel\Services\RebelSpacecraftService;
use Orion\Modules\Asteroid\Services\AsteroidSpawnRequestService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('actionqueue:process', function () {
    $entries = ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
        ->where('end_time', '<=', now())
        ->limit(100) // Batchgröße!
        ->get();

    foreach ($entries as $entry) {
        // Status auf PROCESSING setzen, damit kein anderer Worker sie nimmt
        $entry->status = QueueStatusType::STATUS_PROCESSING;
        $entry->save();

        ProcessActionQueue::dispatch($entry->id);
    }
})->purpose('Process the action queue');

Artisan::command('actionqueue:reset-stuck', function () {
    ActionQueue::where('status', QueueStatusType::STATUS_PROCESSING)
        ->where('updated_at', '<', now()->subMinutes(10))
        ->orWhere('end_time', '<', now()->subMinutes(10))
        ->update(['status' => QueueStatusType::STATUS_IN_PROGRESS]);
})->purpose('Reset stuck processing actions')->everyFiveMinutes();

Artisan::command('actionqueue:processbatch', function () {
    $batchSize = 5;  // Anzahl der IDs pro Job
    $chunkSize = 25; // Anzahl der IDs, die auf einmal aus DB geholt werden

    ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
        ->where('end_time', '<=', now())
        ->select('id')
        ->chunk($chunkSize, function ($chunk) use ($batchSize) {
            $ids = $chunk->pluck('id')->toArray();

            // Status direkt auf PROCESSING setzen, damit kein anderer Worker die IDs nimmt
            ActionQueue::whereIn('id', $ids)
                ->update(['status' => QueueStatusType::STATUS_PROCESSING]);

            // In kleine Batches aufteilen und dispatchen
            foreach (array_chunk($ids, $batchSize) as $batch) {
                ProcessActionQueueBatch::dispatch($batch);
            }
        });
})->purpose('Process the action queue')->everyMinute();

Artisan::command('game:rebel-generate-all', function (
    RebelResourceService $resourceService,
    RebelSpacecraftService $spacecraftService,
    RebelDifficultyService $difficultyService
) {
    $globalDifficulty = $difficultyService->calculateGlobalDifficulty();

    foreach (Rebel::all() as $rebel) {
        $resourceService->generateResources($rebel, null, $globalDifficulty);
        $spacecraftService->spendResourcesForFleet($rebel, $globalDifficulty);
    }

    $this->info('Ressourcen und Spacecrafts generiert!');
})->purpose('Generiert Ressourcen und Raumschiffe für alle Rebels')->hourly();

Artisan::command('game:generate-scheduled-asteroids', function (
    AsteroidSpawnRequestService $service,
) {
    $service->processRequestedAsteroidSpawns();

    $this->info("Processed asteroid spawn requests.");

})->purpose('Generiert planmäßig Asteroiden')->everyFifteenMinutes();
