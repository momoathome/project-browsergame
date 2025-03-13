<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SelectedAsteroidResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y,
            'pixel_size' => $this->pixel_size,
            'resources' => $this->whenLoaded('resources', function () {
                return $this->resources->map(function ($resource) {
                    return [
                        'resource_type' => $resource->resource_type,
                        'amount' => $resource->amount,
                    ];
                });
            }, []), // Leeres Array als Fallback
        ];
    }
}
