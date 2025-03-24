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

    /**
     * Gibt eine Zuordnung von Ressourcennamen zu IDs zurück
     * 
     * @return array Assoziatives Array mit Ressourcennamen als Schlüssel und IDs als Werte
     */
    public function getResourceIdMapping(): array
    {
        return Resource::pluck('id', 'name')->toArray();
    }
}
