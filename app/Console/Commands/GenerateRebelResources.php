<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Rebel\Models\Rebel;
use Orion\Modules\Rebel\Services\RebelResourceService;

class GenerateRebelResources extends Command
{
    protected $signature = 'game:rebel-generate-resources
                            {--ticks=10 : Anzahl der vergangenen Ticks}';

    protected $description = 'Generiert Ressourcen für alle Rebels';

    public function handle(RebelResourceService $service)
    {
        foreach (Rebel::all() as $rebel) {
            $service->generateResources($rebel, (int)$this->option('ticks'));
        }
        $this->info('Ressourcen generiert!');
    }
}
