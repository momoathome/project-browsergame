<?php

namespace Orion\Modules\Station\Http\Controllers;

use Orion\Modules\Station\Services\StationService;

use Orion\Modules\Station\Models\Station;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function __construct(
        private readonly StationService $StationService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(Station $sation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Station $sation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Station $sation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Station $sation)
    {
        //
    }
}
