<?php

namespace Orion\Modules\Rebel\Services;

use Orion\Modules\Rebel\Repositories\RebelRepository;

readonly class RebelService
{

    public function __construct(
        private readonly RebelRepository $rebelRepository
    ) {
    }

    // Add service logic here
    public function getAllRebels()
    {
        return $this->rebelRepository->getAllRebels();
    }

    public function findRebelById(int $id)
    {
        return $this->rebelRepository->findRebelById($id);
    }

    public function findRebelByName(string $name)
    {
        return $this->rebelRepository->findRebelByName($name);
    }

    public function findRebelByFaction(string $faction)
    {
        return $this->rebelRepository->findRebelByFaction($faction);
    }

}
