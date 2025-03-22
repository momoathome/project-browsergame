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
}
