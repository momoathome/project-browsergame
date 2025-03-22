<?php

namespace Orion\Modules\Resource\Http\Controllers;

use Orion\Modules\Resource\Services\ResourceService;

use App\Http\Controllers\Controller;
use Orion\Modules\Resource\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function __construct(
        private readonly ResourceService $resourceService
        )
    {
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
    public function show(resource $resource)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(resource $resource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, resource $resource)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(resource $resource)
    {
        //
    }
}
