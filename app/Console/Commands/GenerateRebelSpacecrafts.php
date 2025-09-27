<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Rebel\Services\RebelSpacecraftService;

class GenerateRebelSpacecrafts extends Command
{
    protected $signature = 'game:rebel-generate-spacecrafts
                            {--count=5 : Anzahl der zu generierenden Raumschiffe pro Rebel}';
    
    protected $description = 'Generiert Raumschiffe für alle Rebels';

    public function handle(RebelSpacecraftService $service)
    {
        foreach (Rebel::all() as $rebel) {
            $service->spendResourcesForFleet($rebel);
        }
        $this->info('Spacecrafts generiert!');
    }
}
