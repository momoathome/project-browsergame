<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsteroidExploreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'asteroid_id' => 'required|exists:asteroids,id',
            'spacecrafts' => 'required|array',
            'spacecrafts.*' => 'integer|min:0',
        ];
    }

    public function getAsteroidId(): int
    {
        return $this->validated('asteroid_id');
    }

    public function getSpacecrafts(): array
    {
        return $this->validated('spacecrafts');
    }
}
