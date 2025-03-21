<?php

namespace Orion\Modules\Asteroid\Http\Requests;

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

}
