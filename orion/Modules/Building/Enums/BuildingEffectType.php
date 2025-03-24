<?php

namespace Orion\Modules\Building\Enums;

enum BuildingEffectType: string
{
    case ADDITIVE = 'additive';   // Jedes Level addiert einen festen Wert
    case MULTIPLICATIVE = 'multiplicative'; // Jedes Level multipliziert einen Wert
    case EXPONENTIAL = 'exponential';  // Jedes Level erhöht exponentiell
    case LOGARITHMIC = 'logarithmic';  // Jedes Level erhöht logarithmisch
}
