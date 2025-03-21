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
}
