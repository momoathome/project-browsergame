<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client;
use Orion\Modules\Asteroid\Services\AsteroidSearch;

class ConfigureMeilisearch extends Command
{
    protected $signature = 'meilisearch:configure';
    protected $description = 'Konfiguriert die Meilisearch-Indizes für die Asteroiden-Suche';

    public function handle()
    {
        $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));
        $index = $client->index('asteroids');

        // Suchbare Attribute mit besserer Priorisierung
        $index->updateSearchableAttributes([
            'name',
            'all_resources',
            'resources_flat',
            'resource_types',
            'size',
            'resources.type'
        ]);

        $index->updateFilterableAttributes([
            'size',
            'resource_types',
            'resource_map',
            'x',
            'y'
        ]);

        // Sortierbare Attribute
        $index->updateSortableAttributes([
            'size',
            'x',
            'y'
        ]);

        // Erweiterte Einstellungen für die Suche
        $index->updateSettings([
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness'
            ],
            'distinctAttribute' => 'id',
            'searchableAttributes' => ['*'],
            'displayedAttributes' => ['*'],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 3,
                    'twoTypos' => 6
                ]
            ],
            // Tokenisierungseinstellungen
            // 'separatorTokens' => [' ', '.','-', '_'],
            'nonSeparatorTokens' => ['à', 'â', 'æ', 'é', 'è', 'ê', 'ë', 'î', 'ï', 'ô', 'œ', 'ù', 'û', 'ü', 'ÿ',]
        ]);

        // Synonyme hinzufügen
        $synonyms = $this->getResourceSynonyms();
        $index->updateSynonyms($synonyms);

        $this->info('Meilisearch-Index für Asteroiden erfolgreich konfiguriert.');
    }

    private function getResourceSynonyms()
    {
        // Hole die vorhandenen Synonyme aus deinem AsteroidSearch-Service
        $synonymsMap = app(AsteroidSearch::class)->getResourceSynonyms();

        // Konvertiere das Format für Meilisearch
        $meilisearchSynonyms = [];

        foreach ($synonymsMap as $synonym => $resources) {
            foreach ($resources as $resource) {
                if (!isset($meilisearchSynonyms[strtolower($resource)])) {
                    $meilisearchSynonyms[strtolower($resource)] = [];
                }
                $meilisearchSynonyms[strtolower($resource)][] = $synonym;

                // Auch den Synonym-Begriff selbst als Synonym hinzufügen
                if (!isset($meilisearchSynonyms[$synonym])) {
                    $meilisearchSynonyms[$synonym] = [];
                }
                $meilisearchSynonyms[$synonym][] = strtolower($resource);
            }
        }

        return $meilisearchSynonyms;
    }
}
