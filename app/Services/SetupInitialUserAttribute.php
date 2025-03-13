<?php

namespace App\Services;

use App\Models\UserAttribute;

class SetupInitialUserAttribute
{
  /**
   * Setup initial resources for a new user.
   *
   * @param int $userId
   */
  public function create(int $userId): void
  {
    $attributesConfig = config('game.user_attributes.attributes');

    foreach ($attributesConfig as $attributeConfig) {
      UserAttribute::create([
        'user_id' => $userId,
        'attribute_name' => $attributeConfig['attribute_name'],
        'attribute_value' => $attributeConfig['attribute_value'],
      ]);
    }
  }
}
