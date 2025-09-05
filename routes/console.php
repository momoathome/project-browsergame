<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use App\Jobs\ProcessActionQueue;
use App\Jobs\ProcessActionQueueBatch;

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
    $batchSize = 50;
    $entries = ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
        ->where('end_time', '<=', now())
        ->pluck('id')
        ->get();

    ActionQueue::whereIn('id', $entries)
        ->update(['status' => QueueStatusType::STATUS_PROCESSING]);

    // In Batches dispatchen
    foreach (array_chunk($entries, $batchSize) as $batch) {
        ProcessActionQueueBatch::dispatch($batch);
    }
})->purpose('Process the action queue')->everyMinute();
