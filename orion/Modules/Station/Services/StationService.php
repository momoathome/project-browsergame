<?php

namespace Orion\Modules\Station\Services;

use Orion\Modules\Station\Repositories\StationRepository;

readonly class StationService
{

    public function __construct(
        private StationRepository $stationRepository
    ) {
    }

    // Add service logic here
    public function getAllStations()
    {
        return $this->stationRepository->getAllStations();
    }
    

    public function findStationById(int $id)
    {
        return $this->stationRepository->findStationById($id);
    }

    public function findStationByUserId(int $userId)
    {
        return $this->stationRepository->findStationByUserId($userId);
    }
}
