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

    // Add repository methods here
}
