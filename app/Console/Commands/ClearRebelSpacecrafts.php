<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Orion\Modules\Rebel\Models\RebelSpacecraft;

class ClearRebelSpacecrafts extends Command
{
    protected $signature = 'game:rebel-clear-spacecrafts';
    protected $description = 'Setzt alle Rebel-Raumschiffe auf 0 zurück';

    public function handle()
    {
        $count = RebelSpacecraft::query()->update(['amount' => 0]);
        $this->info("Alle Rebel-Spacecrafts wurden zurückgesetzt ($count Einträge).");
    }
}

