<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpacecraftResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'count' => $this->count,
            'cargo' => $this->cargo,
            'combat' => $this->combat,
            'unlocked' => $this->unlocked,
            'speed' => $this->speed,
            'build_time' => $this->build_time,
            'crew_limit' => $this->crew_limit,
            'research_cost' => $this->research_cost,
            'details' => [
                'id' => $this->details->id,
                'name' => $this->details->name,
                'type' => $this->details->type,
                'description' => $this->details->description,
                'image' => $this->details->image,
            ],
        ];
    }
}
