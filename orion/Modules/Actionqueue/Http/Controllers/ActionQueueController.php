<?php

namespace Orion\Modules\Actionqueue\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Orion\Modules\Actionqueue\Services\ActionQueueService;

class ActionQueueController extends Controller
{
    public function __construct(
        private readonly ActionQueueService $queueService
    ) {
    }
    /**
     * Get a listing of the action queue for the authenticated user.
     */
    public function index()
    {
        if (Auth::check()) {
            $queue = $this->queueService->getUserQueue(Auth::user()->id);
            return response()->json(['queue' => $queue]);
        }
        return response()->json(['queue' => []]);
    }

    /**
     * Process the action queue for the authenticated user.
     */
    public function process()
    {
        if (Auth::check()) {
            $this->queueService->processQueueForUser(Auth::user()->id);
            $queue = $this->queueService->getUserQueue(Auth::user()->id);
            return response()->json(['queue' => $queue]);
        }
        return response()->json(['queue' => []]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
