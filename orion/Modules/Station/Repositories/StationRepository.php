<?php

namespace Orion\Modules\Station\Repositories;

use Orion\Modules\Station\Models\Station;
use Orion\Modules\Station\Models\StationRegion;
readonly class StationRepository
{
    // Add repository logic here
    public function getAllStations()
    {
        return Station::all();
    }

    public function findStationById(int $id)
    {
        return Station::find($id);
    }

    public function findStationByUserId(int $userId)
    {
        return Station::where('user_id', $userId)->first();
    }

    public function getReservedStationRegions()
    {
        return StationRegion::where('used', false)->get();
    }
}
