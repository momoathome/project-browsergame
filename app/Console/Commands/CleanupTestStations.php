<?php

namespace App\Console\Commands;

use Orion\Modules\Station\Models\Station;
use Orion\Modules\Station\Services\SetupInitialStation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class cleanupTestStations extends Command
{
    /**
     * Der Befehlsname und Signatur der Konsolenanwendung.
     *
     * @var string
     */
    protected $signature = 'game:cleanup-test-stations 
                            {--all : Löscht alle Testdaten (User und Stationen)}
                            {--stations-only : Löscht nur Teststationen}
                            {--users-only : Löscht nur Testuser}
                            {--pattern=testuser : Muster für die Identifizierung von Testdaten}';

    /**
     * Die Konsolenbefehls-Beschreibung.
     *
     * @var string
     */
    protected $description = 'Löscht Testdaten (Benutzer und/oder Stationen) aus dem System';

    /**
     * Führt den Konsolenbefehl aus.
     *
     * @return int
     */
    public function handle()
    {
        $pattern = $this->option('pattern');
        $stationsOnly = $this->option('stations-only');
        $usersOnly = $this->option('users-only');
        $all = $this->option('all');

        if (!$stationsOnly && !$usersOnly) {
            $all = true;
        }

        if ($all || $usersOnly) {
            $this->cleanupTestUsers($pattern);
        }

        if ($all || $stationsOnly) {
            $this->cleanupTestStations($pattern);
        }

        // Cache für Stationen zurücksetzen
        $this->info('Setze Cache zurück...');
        SetupInitialStation::clearCache();
        $this->info('Cache zurückgesetzt.');

        return 0;
    }

    /**
     * Lösche Test-Benutzer basierend auf dem Muster.
     *
     * @param string $pattern
     * @return void
     */
    private function cleanupTestUsers(string $pattern)
    {
        $this->info("Suche nach Testbenutzern mit Muster '{$pattern}'...");

        $query = User::where('name', 'like', "%{$pattern}%")
            ->orWhere('email', 'like', "%{$pattern}%");

        $count = $query->count();

        if ($count === 0) {
            $this->info('Keine Testbenutzer gefunden.');
            return;
        }

        $this->info("Gefunden: {$count} Testbenutzer");

        if ($this->confirm("Möchten Sie {$count} Testbenutzer löschen?", true)) {
            // Stationen werden durch Fremdschlüsselbeziehungen automatisch gelöscht,
            // wenn ON DELETE CASCADE in der Datenbank konfiguriert ist
            $deleted = $query->delete();
            $this->info("{$deleted} Testbenutzer wurden erfolgreich gelöscht.");
        } else {
            $this->info('Vorgang abgebrochen.');
        }
    }

    /**
     * Lösche Teststationen basierend auf dem Muster.
     *
     * @param string $pattern
     * @return void
     */
    private function cleanupTestStations(string $pattern)
    {
        $this->info("Suche nach Teststationen von Benutzern mit Muster '{$pattern}'...");

        $testUserIds = User::where('name', 'like', "%{$pattern}%")
            ->orWhere('email', 'like', "%{$pattern}%")
            ->pluck('id')
            ->toArray();

        if (empty($testUserIds)) {
            $this->info('Keine passenden Benutzer für Stationen gefunden.');
            return;
        }

        $query = Station::whereIn('user_id', $testUserIds);
        $count = $query->count();

        if ($count === 0) {
            $this->info('Keine Teststationen gefunden.');
            return;
        }

        $this->info("Gefunden: {$count} Teststationen");

        if ($this->confirm("Möchten Sie {$count} Teststationen löschen?", true)) {
            $deleted = $query->delete();
            $this->info("{$deleted} Teststationen wurden erfolgreich gelöscht.");
        } else {
            $this->info('Vorgang abgebrochen.');
        }
    }
}
