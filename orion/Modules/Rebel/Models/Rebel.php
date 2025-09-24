<?php

namespace Orion\Modules\Rebel\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class Rebel extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
        'faction',
        'x',
        'y'
    ];

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'faction' => $this->faction,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
