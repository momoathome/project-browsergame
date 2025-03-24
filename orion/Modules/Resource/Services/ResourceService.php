<?php

namespace Orion\Modules\Resource\Services;

use Orion\Modules\Resource\Repositories\ResourceRepository;

readonly class ResourceService
{

    public function __construct(
        private readonly ResourceRepository $resourceRepository
    ) {
    }

    // Add service logic here
    public function getAllResources()
    {
        return $this->resourceRepository->getAllResources();
    }

    public function findResourceById(int $id)
    {
        return $this->resourceRepository->findResourceById($id);
    }

    public function findResourceByName(string $name)
    {
        return $this->resourceRepository->findResourceByName($name);
    }

    public function findResourceByType(string $resourceType)
    {
        return $this->resourceRepository->findResourceByType($resourceType);
    }
}
