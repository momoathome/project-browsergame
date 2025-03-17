<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

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

        return $array;
    }

}
