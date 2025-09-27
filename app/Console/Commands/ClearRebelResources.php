<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Rebel\Models\RebelResource;

class ClearRebelResources extends Command
{
    protected $signature = 'game:rebel-clear-resources';
    protected $description = 'Setzt alle Rebel-Ressourcen auf 0 zurück';

    public function handle()
    {
        $count = RebelResource::query()->update(['amount' => 0]);
        $this->info("Alle Rebel-Ressourcen wurden zurückgesetzt ($count Einträge).");
    }
}

