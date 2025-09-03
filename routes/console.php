<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use App\Jobs\ProcessActionQueue;
use App\Jobs\ProcessActionQueueBatch;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

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
})->purpose('Reset stuck processing actions');

Artisan::command('actionqueue:processbatch', function () {
    $batchSize = 20;
    $entries = ActionQueue::where('status', QueueStatusType::STATUS_IN_PROGRESS)
        ->where('end_time', '<=', now())
        ->limit(200)
        ->get();

    $ids = $entries->pluck('id')->toArray();

    ActionQueue::whereIn('id', $ids)
        ->update(['status' => QueueStatusType::STATUS_PROCESSING]);

    // In Batches dispatchen
    foreach (array_chunk($ids, $batchSize) as $batch) {
        ProcessActionQueueBatch::dispatch($batch);
    }
})->purpose('Process the action queue')->everyMinute();
