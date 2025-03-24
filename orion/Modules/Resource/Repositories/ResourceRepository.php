<?php

namespace Orion\Modules\Resource\Repositories;

use Orion\Modules\Resource\Models\Resource;

readonly class ResourceRepository
{
    // Add repository logic here
    public function getAllResources()
    {
        return Resource::all();
    }

    public function findResourceById(int $id)
    {
        return Resource::find($id);
    }

    public function findResourceByName(string $name)
    {
        return Resource::where('name', $name)->first();
    }

    public function findResourceByType(string $resourceType)
    {
        return Resource::where('name', $resourceType)->first();
    }
}
