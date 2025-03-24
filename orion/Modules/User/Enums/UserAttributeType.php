<?php

namespace Orion\Modules\User\Enums;

enum UserAttributeType: string
{
    case PRODUCTION_SPEED = 'production_speed';
    case CREW_LIMIT = 'crew_limit';
    case STORAGE = 'storage';
    case RESEARCH_POINTS = 'research_points';
    case SCAN_RANGE = 'scan_range';
    case BASE_DEFENSE = 'base_defense';
}
