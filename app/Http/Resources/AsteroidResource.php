<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AsteroidResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'x' => $this->x,
            'y' => $this->y,
            'pixel_size' => $this->pixel_size,
        ];
    }
}
