<?php

namespace Orion\Modules\Spacecraft\Services;

use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;

readonly class SpacecraftService
{

    public function __construct(private SpacecraftRepository $spacecraftRepository)
    {
    }

    // Add service logic here
    public function getAllSpacecraftsByUserId(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserId($userId);
    }
}
