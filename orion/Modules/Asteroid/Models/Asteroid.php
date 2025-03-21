<?php

namespace Orion\Modules\Asteroid\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Orion\Modules\Asteroid\Models\AsteroidResource;

class Asteroid extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'size',
        'base',
        'multiplier',
        'value',
        'x',
        'y',
        'pixel_size',
    ];

    protected $indexes = ['id', 'name', 'size', 'x', 'y', 'pixel_size'];

    public function resources()
    {
        return $this->hasMany(AsteroidResource::class);
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();
    
        if (!$this->relationLoaded('resources')) {
            $this->load('resources');
        }
    
        // Ressourcen als durchsuchbare Attribute hinzufügen
        $array['resources'] = $this->resources->map(function ($resource) {
            return [
                'type' => $resource->resource_type,
                'amount' => $resource->amount,
            ];
        })->toArray();
    
        // Liste aller Ressourcentypen als eigenes Feld für bessere Filterung
        $array['resource_types'] = $this->resources->pluck('resource_type')->toArray();
    
        // Alle Ressourcen als String für bessere Volltextsuche
        $array['all_resources'] = implode(' ', $this->resources->pluck('resource_type')->toArray());
        
        // Neue Felder für bessere Ressourcensuche
        $resourceMap = [];
        foreach ($this->resources as $resource) {
            $resourceMap[$resource->resource_type] = $resource->amount;
        }
        $array['resource_map'] = $resourceMap;
        
        // Flaches Ressourcen-Attribut für direkte Suche
        // Format: "Carbon:381 Hydrogenium:324"
        $array['resources_flat'] = $this->resources->map(function ($resource) {
            return $resource->resource_type . ':' . $resource->amount;
        })->implode(' ');
    
        return $array;
    }

}
