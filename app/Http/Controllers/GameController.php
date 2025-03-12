<?php

namespace App\Http\Controllers;

use App\Services\QueueService;
use App\Models\ActionQueue;
use Illuminate\Http\Request;

class GameController extends Controller
{
    private $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function startMining(Request $request)
    {
        $userId = auth()->id();
        $asteroidId = $request->input('asteroid_id');
        $duration = 7200; // 2 Stunden in Sekunden
        $details = ['asteroid_type' => 'iron', 'amount' => 500];

        $queueItem = $this->queueService->addToQueue($userId, 'mining', $asteroidId, $duration, $details);

        return response()->json(['message' => 'Mining started', 'queue_item' => $queueItem]);
    }

    public function getPlayerQueue()
    {
        $userId = auth()->id();
        $queue = ActionQueue::where('user_id', $userId)->get();

        return response()->json(['queue' => $queue]);
    }
}
