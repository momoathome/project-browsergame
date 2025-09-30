<?php

namespace App\Events;

use App\Models\User;
use Orion\Modules\Asteroid\Models\Asteroid;
use Illuminate\Foundation\Events\Dispatchable;

class AsteroidMined
{
    use Dispatchable;

    public function __construct(
        public Asteroid $asteroid,
        public User $user,
        public array $resourcesExtracted
    ) {}
}
