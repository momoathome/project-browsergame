<?php

namespace App\Services;

use App\Models\Station;

class SetupInitialStation
{

    public function create(int $userId, string $userName)
    {
        $initialCoordinateX = $this->generateInitialCoordinate();
        $initialCoordinateY = $this->generateInitialCoordinate();

        return Station::create([
            'user_id' => $userId,
            'name' => $userName,
            'coordinate_x' => $initialCoordinateX,
            'coordinate_y' => $initialCoordinateY,
        ]);
    }

    private function generateInitialCoordinate(): int
    {
        // You can implement a more complex logic here if needed.
        // For simplicity, returning a fixed value or a random number.
        return rand(5000, 30000);
    }
}
