<?php

namespace Orion\Modules\Rebel\Repositories;

use Orion\Modules\Rebel\Models\Rebel;

readonly class RebelRepository
{
    public function getAllRebels()
    {
        return Rebel::all();
    }

    public function findRebelById(int $id)
     {
         return Rebel::find($id);
     }

     public function findRebelByName(string $name)
     {
         return Rebel::where('name', $name)->first();
     }

     public function findRebelByFaction(string $faction)
     {
         return Rebel::where('faction', $faction)->first();
     }

    public function updateLastInteraction(int $id): void
    {
        Rebel::where('id', $id)->update(['last_interaction' => now()]);
    }

    public function incrementDefeatedCount(int $id): void
    {
        Rebel::where('id', $id)->increment('defeated_count');
    }

    public function getRebelWithRelations(int $id)
    {
        return Rebel::with('resources.resource', 'spacecrafts.details')->find($id);
    }

    public function getAllRebelsWithRelations()
    {
        return Rebel::with('resources.resource', 'spacecrafts.details')->get();
    }

    // Add repository methods here
}
