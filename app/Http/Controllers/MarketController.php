<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Hole alle Marktdaten
        $market = Market::with('resource')
        ->orderBy('id', 'asc')
        ->get();

        // Übergibt die Marktdaten an die Inertia-Seite
        return Inertia::render('Market', [
            'market' => $market,
        ]);
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
    public function show(market $market)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(market $market)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, market $market)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(market $market)
    {
        //
    }
}
