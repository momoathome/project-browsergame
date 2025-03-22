<?php

namespace Orion\Modules\Spacecraft\Services;

use Orion\Modules\Spacecraft\Repositories\SpacecraftRepository;

readonly class SpacecraftService
{

    public function __construct(
        private SpacecraftRepository $spacecraftRepository
    ) {
    }

    public function getAllSpacecraftsByUserId(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserId($userId);
    }

    public function getAllSpacecraftsByUserIdWithDetails(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetails($userId);
    }
    
    public function getAllSpacecraftsByUserIdWithDetailsAndResources(int $userId)
    {
        return $this->spacecraftRepository->getAllSpacecraftsByUserIdWithDetailsAndResources($userId);
    }
}
